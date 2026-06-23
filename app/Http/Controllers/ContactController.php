<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('contact.index');
    }

    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $submission = ContactSubmission::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'subject' => $validated['subject'] ?? null,
            'message' => $validated['message'],
            'status' => 'unread',
        ]);

        // Notify admin by email (non-critical; submission is already saved)
        $adminEmail = Setting::get('contact.admin_email', config('mail.from.address'));

        if ($adminEmail) {
            try {
                Mail::raw(
                    "New contact message from {$submission->name} ({$submission->email}):\n\n{$submission->message}",
                    function ($mail) use ($adminEmail, $submission) {
                        $mail->to($adminEmail)
                            ->subject('New Contact Message: '.($submission->subject ?? 'No Subject'));
                    }
                );
            } catch (\Throwable $e) {
                Log::error('Contact admin notification failed', [
                    'submission_id' => $submission->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('contact.index')->with('success', 'Your message has been sent. We\'ll get back to you soon!');
    }
}
