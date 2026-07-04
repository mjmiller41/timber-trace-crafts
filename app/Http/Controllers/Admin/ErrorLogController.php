<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ErrorLogController extends Controller
{
    /**
     * Cap how much of a log file we read into memory (tail only). Laravel log
     * files can grow to hundreds of MB; we only surface the most recent tail.
     */
    private const MAX_BYTES = 2_000_000;

    private const MAX_ENTRIES = 400;

    public function index(Request $request): View
    {
        $files = $this->logFiles();

        $current = (string) $request->query('file', $files[0] ?? '');
        // Guard against path traversal — only files we enumerated are allowed.
        if (! in_array($current, $files, true)) {
            $current = $files[0] ?? '';
        }

        $level = strtoupper((string) $request->query('level', ''));

        $entries = $current ? $this->parse($current) : [];

        if ($level) {
            $entries = array_values(array_filter($entries, fn ($e) => $e['level'] === $level));
        }

        $levels = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'];

        return view('admin.error-logs.index', [
            'files' => $files,
            'current' => $current,
            'level' => $level,
            'levels' => $levels,
            'entries' => array_slice($entries, 0, self::MAX_ENTRIES),
        ]);
    }

    /**
     * @return list<string> log file basenames, newest first
     */
    private function logFiles(): array
    {
        $dir = storage_path('logs');

        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir.'/*.log') ?: [];

        // Newest first by modified time.
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return array_map('basename', $files);
    }

    /**
     * Parse a log file's tail into structured entries, newest first.
     *
     * @return list<array{timestamp: string, env: string, level: string, message: string}>
     */
    private function parse(string $basename): array
    {
        $path = storage_path('logs/'.$basename);

        if (! is_file($path)) {
            return [];
        }

        $size = filesize($path);
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        if ($size > self::MAX_BYTES) {
            fseek($handle, -self::MAX_BYTES, SEEK_END);
            fgets($handle); // discard the partial first line
        }

        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        // Each entry starts with "[YYYY-MM-DD HH:MM:SS] env.LEVEL:".
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}[^\]]*)\] (\w+)\.(\w+): (.*)$/s';

        $lines = preg_split('/\n(?=\[\d{4}-\d{2}-\d{2})/', $contents) ?: [];
        $entries = [];

        foreach ($lines as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }

            if (preg_match($pattern, $chunk, $m)) {
                $entries[] = [
                    'timestamp' => $m[1],
                    'env' => $m[2],
                    'level' => strtoupper($m[3]),
                    'message' => Str::limit(trim($m[4]), 8000),
                ];
            }
        }

        return array_reverse($entries);
    }
}
