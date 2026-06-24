<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $imap = new ImapService;
            $page = max(1, (int) $request->get('page', 1));
            $messages = $imap->messages($page, 25);
            $total = $imap->totalCount();
            $unread = $imap->unreadCount();
            $error = null;
        } catch (\Throwable $e) {
            $messages = collect();
            $total = 0;
            $unread = 0;
            $error = $e->getMessage();
        }

        return view('admin.inbox.index', compact('messages', 'total', 'unread', 'page', 'error'));
    }

    public function show(int $msgno): View|RedirectResponse
    {
        try {
            $imap = new ImapService;
            $message = $imap->message($msgno);
        } catch (\Throwable $e) {
            return redirect()->route('admin.inbox.index')->with('error', 'Could not load message: '.$e->getMessage());
        }

        return view('admin.inbox.show', compact('message'));
    }

    public function compose(): View
    {
        return view('admin.inbox.compose', ['replyTo' => null, 'subject' => '', 'to' => '']);
    }

    public function reply(int $msgno): View|RedirectResponse
    {
        try {
            $imap = new ImapService;
            $message = $imap->message($msgno);
        } catch (\Throwable $e) {
            return redirect()->route('admin.inbox.index')->with('error', 'Could not load message: '.$e->getMessage());
        }

        $subject = str_starts_with($message['subject'], 'Re:') ? $message['subject'] : 'Re: '.$message['subject'];

        return view('admin.inbox.compose', [
            'replyTo' => $message,
            'subject' => $subject,
            'to' => $message['from_email'],
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        try {
            Mail::html($validated['body'], function ($mail) use ($validated) {
                $mail->to($validated['to'])
                    ->subject($validated['subject'])
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to send: '.$e->getMessage());
        }

        return redirect()->route('admin.inbox.index')->with('success', 'Email sent to '.$validated['to'].'.');
    }

    public function destroy(int $msgno): RedirectResponse
    {
        try {
            $imap = new ImapService;
            $imap->delete($msgno);
        } catch (\Throwable $e) {
            return redirect()->route('admin.inbox.index')->with('error', 'Could not delete message: '.$e->getMessage());
        }

        return redirect()->route('admin.inbox.index')->with('success', 'Message deleted.');
    }
}
