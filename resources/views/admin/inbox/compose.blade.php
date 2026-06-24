@extends('layouts.admin')

@push('title'){{ $replyTo ? 'Reply' : 'Compose' }} @endpush

@section('content')

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
    <h1 style="font-family: 'Playfair Display', serif; font-weight: 300; font-size: 1.75rem; color: #2d2d2d; margin: 0;">
        {{ $replyTo ? 'Reply' : 'New Message' }}
    </h1>
    <a href="{{ route('admin.inbox.index') }}" style="font-size: 0.8125rem; color: #6b7280; text-decoration: none;">
        ← Back to Inbox
    </a>
</div>

@if(session('error'))
    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 0.875rem 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 0.875rem;">
        {{ session('error') }}
    </div>
@endif

<div style="background: white; border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden;">
    <form method="POST" action="{{ route('admin.inbox.send') }}">
        @csrf

        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f3f4f6;">

            {{-- To --}}
            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6;">
                <label for="to" style="font-size: 0.8125rem; color: #9ca3af; width: 60px; flex-shrink: 0;">To</label>
                <input
                    type="email"
                    id="to"
                    name="to"
                    value="{{ old('to', $to) }}"
                    placeholder="recipient@example.com"
                    required
                    style="flex: 1; border: none; outline: none; font-size: 0.875rem; color: #111827; font-family: 'Montserrat', sans-serif; background: transparent;"
                >
            </div>

            {{-- Subject --}}
            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem 0;">
                <label for="subject" style="font-size: 0.8125rem; color: #9ca3af; width: 60px; flex-shrink: 0;">Subject</label>
                <input
                    type="text"
                    id="subject"
                    name="subject"
                    value="{{ old('subject', $subject) }}"
                    placeholder="Subject"
                    required
                    maxlength="255"
                    style="flex: 1; border: none; outline: none; font-size: 0.875rem; color: #111827; font-family: 'Montserrat', sans-serif; background: transparent;"
                >
            </div>

        </div>

        {{-- Body --}}
        <div style="padding: 1.25rem 1.5rem;">
            <textarea
                id="body"
                name="body"
                rows="14"
                required
                placeholder="Write your message here…"
                style="width: 100%; border: none; outline: none; resize: vertical; font-size: 0.875rem; line-height: 1.7; color: #374151; font-family: 'Montserrat', sans-serif; background: transparent; box-sizing: border-box;"
            >{{ old('body') }}@if($replyTo)


---
On {{ $replyTo['date']->format('M j, Y \a\t g:i A') }}, {{ $replyTo['from'] ?: $replyTo['from_email'] }} wrote:

{{ strip_tags($replyTo['body_text'] ?? strip_tags($replyTo['body_html'] ?? '')) }}@endif</textarea>
        </div>

        {{-- Actions --}}
        <div style="padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6; display: flex; align-items: center; gap: 0.75rem;">
            <button type="submit" class="admin-btn admin-btn-primary">Send</button>
            <a href="{{ route('admin.inbox.index') }}" class="admin-btn admin-btn-outline">Cancel</a>
        </div>

    </form>
</div>

@endsection
