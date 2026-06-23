<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(): View
    {
        $submissions = ContactSubmission::orderByDesc('created_at')->paginate(25);

        return view('admin.messages.index', compact('submissions'));
    }

    public function updateStatus(Request $request, ContactSubmission $submission): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:unread,read,replied,archived'],
        ]);

        $submission->update(['status' => $validated['status']]);

        return redirect()->route('admin.messages.index')->with('success', 'Message status updated.');
    }
}
