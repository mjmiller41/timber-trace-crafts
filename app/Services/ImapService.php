<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use IMAP\Connection;

class ImapService
{
    private string $mailbox;

    /** @var Connection|resource */
    private mixed $connection;

    public function __construct()
    {
        $cfg = config('services.imap');
        $flags = $cfg['novalidate_cert'] ? '/novalidate-cert' : '';
        $this->mailbox = sprintf('{%s:%d/imap/%s%s}INBOX', $cfg['host'], $cfg['port'], $cfg['encryption'], $flags);
        $this->connection = imap_open($this->mailbox, $cfg['username'], $cfg['password'])
            ?: throw new \RuntimeException('IMAP connection failed: '.imap_last_error());
    }

    public function __destruct()
    {
        if ($this->connection) {
            imap_close($this->connection);
        }
    }

    public function totalCount(): int
    {
        return imap_num_msg($this->connection);
    }

    public function unreadCount(): int
    {
        $result = imap_search($this->connection, 'UNSEEN');

        return $result ? count($result) : 0;
    }

    /** @return Collection<int, array<string, mixed>> */
    public function messages(int $page = 1, int $perPage = 20): Collection
    {
        $total = $this->totalCount();

        if ($total === 0) {
            return collect();
        }

        // Messages are numbered oldest=1, newest=total. Show newest first.
        $end = $total - (($page - 1) * $perPage);
        $start = max(1, $end - $perPage + 1);

        if ($end < 1) {
            return collect();
        }

        $overviews = imap_fetch_overview($this->connection, "{$start}:{$end}", 0);

        return collect(array_reverse($overviews))->map(fn ($o) => [
            'msgno' => $o->msgno,
            'subject' => $this->decodeMime($o->subject ?? '(no subject)'),
            'from' => $this->decodeMime($o->from ?? ''),
            'from_email' => $o->sender_email ?? $this->extractEmail($o->from ?? ''),
            'date' => $this->safeParseDate($o->date ?? null),
            'seen' => (bool) $o->seen,
            'answered' => (bool) ($o->answered ?? false),
        ]);
    }

    /** @return array<string, mixed> */
    public function message(int $msgno): array
    {
        $overview = imap_fetch_overview($this->connection, (string) $msgno, 0)[0] ?? null;

        if (! $overview) {
            throw new \RuntimeException("Message {$msgno} not found.");
        }

        $structure = imap_fetchstructure($this->connection, $msgno);
        [$html, $plain] = $this->extractBody($msgno, $structure);

        // Mark as read
        imap_setflag_full($this->connection, (string) $msgno, '\\Seen');

        return [
            'msgno' => $msgno,
            'subject' => $this->decodeMime($overview->subject ?? '(no subject)'),
            'from' => $this->decodeMime($overview->from ?? ''),
            'from_email' => $this->extractEmail($overview->from ?? ''),
            'to' => $this->decodeMime($overview->to ?? ''),
            'date' => $this->safeParseDate($overview->date ?? null),
            'seen' => true,
            'body_html' => $html,
            'body_text' => $plain,
        ];
    }

    public function delete(int $msgno): void
    {
        imap_delete($this->connection, (string) $msgno);
        imap_expunge($this->connection);
    }

    /** @return array{0: ?string, 1: ?string} [html, plain] */
    private function extractBody(int $msgno, object $structure, string $partNum = ''): array
    {
        $html = null;
        $plain = null;

        if ($structure->type === TYPEMULTIPART) {
            foreach ($structure->parts as $i => $part) {
                $num = $partNum === '' ? (string) ($i + 1) : "{$partNum}.".($i + 1);
                [$h, $p] = $this->extractBody($msgno, $part, $num);
                $html ??= $h;
                $plain ??= $p;
            }
        } else {
            $num = $partNum === '' ? '1' : $partNum;
            $subtype = strtoupper($structure->subtype ?? '');
            $encoding = $structure->encoding ?? ENCOTHER;

            $body = imap_fetchbody($this->connection, $msgno, $num);
            $body = $this->decode($body, $encoding);
            $charset = $this->charset($structure);

            if ($charset && strtoupper($charset) !== 'UTF-8') {
                $body = $this->safeConvertToUtf8($body, $charset);
            }

            if ($structure->type === TYPETEXT && $subtype === 'HTML') {
                $html = $body;
            } elseif ($structure->type === TYPETEXT) {
                $plain = $body;
            }
        }

        return [$html, $plain];
    }

    /**
     * A garbage sender-controlled Date: header must not throw and blank the
     * whole inbox listing — fall back to "now" for that one message instead.
     */
    private function safeParseDate(?string $date): Carbon
    {
        try {
            return Carbon::parse($date ?? 'now');
        } catch (\Throwable) {
            return Carbon::now();
        }
    }

    /**
     * A sender-controlled charset can be unknown to mbstring — fall back to
     * the raw body rather than throwing and blanking the whole listing.
     */
    private function safeConvertToUtf8(string $body, string $charset): string
    {
        try {
            $converted = @mb_convert_encoding($body, 'UTF-8', $charset);

            return $converted !== false ? $converted : $body;
        } catch (\Throwable) {
            return $body;
        }
    }

    private function decode(string $body, int $encoding): string
    {
        return match ($encoding) {
            ENCBASE64 => base64_decode($body),
            ENCQUOTEDPRINTABLE => quoted_printable_decode($body),
            default => $body,
        };
    }

    private function charset(object $structure): ?string
    {
        foreach ($structure->parameters ?? [] as $param) {
            if (strtolower($param->attribute) === 'charset') {
                return $param->value;
            }
        }

        return null;
    }

    private function decodeMime(string $value): string
    {
        $decoded = imap_mime_header_decode($value);
        $result = '';
        foreach ($decoded as $part) {
            $charset = $part->charset === 'default' ? 'UTF-8' : $part->charset;
            $result .= mb_convert_encoding($part->text, 'UTF-8', $charset);
        }

        return $result ?: $value;
    }

    private function extractEmail(string $from): string
    {
        if (preg_match('/<([^>]+)>/', $from, $m)) {
            return $m[1];
        }

        return $from;
    }
}
