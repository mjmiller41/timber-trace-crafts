@extends('layouts.admin')

@push('title'){{ Str::limit($message['subject'], 40) }} @endpush

@section('content')

{{-- Back + actions --}}
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
    <a href="{{ route('admin.inbox.index') }}" style="font-size: 0.8125rem; color: #6b7280; text-decoration: none; display: flex; align-items: center; gap: 0.375rem;">
        ← Back to Inbox
    </a>
    <div style="display: flex; gap: 0.5rem;">
        <a href="{{ route('admin.inbox.reply', $message['msgno']) }}" class="admin-btn admin-btn-primary">Reply</a>
        <form method="POST" action="{{ route('admin.inbox.destroy', $message['msgno']) }}"
              onsubmit="return confirm('Delete this message?')">
            @csrf @method('DELETE')
            <button type="submit" class="admin-btn admin-btn-danger">Delete</button>
        </form>
    </div>
</div>

<div style="background: white; border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden;">

    {{-- Header --}}
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f3f4f6;">
        <h2 style="font-family: 'Playfair Display', serif; font-weight: 300; font-size: 1.375rem; color: #111827; margin: 0 0 0.75rem;">
            {{ $message['subject'] }}
        </h2>
        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
            <div style="font-size: 0.8125rem; color: #6b7280;">
                <span style="color: #9ca3af;">From:</span>
                <span style="color: #111827; font-weight: 500; margin-left: 0.5rem;">{{ $message['from'] ?: $message['from_email'] }}</span>
                <span style="color: #9ca3af; margin-left: 0.25rem;">&lt;{{ $message['from_email'] }}&gt;</span>
            </div>
            <div style="font-size: 0.8125rem; color: #6b7280;">
                <span style="color: #9ca3af;">To:</span>
                <span style="margin-left: 0.5rem;">{{ $message['to'] }}</span>
            </div>
            <div style="font-size: 0.8125rem; color: #9ca3af;">
                {{ $message['date']->format('F j, Y \a\t g:i A') }}
            </div>
        </div>
    </div>

    {{-- Body — sandboxed to prevent script execution from untrusted email HTML --}}
    <div style="padding: 1.5rem;">
        @if($message['body_html'])
            <iframe
                srcdoc="{{ $message['body_html'] }}"
                sandbox=""
                referrerpolicy="no-referrer"
                style="width: 100%; border: none; min-height: 300px;"
                onload="this.style.height = (this.contentDocument.body.scrollHeight + 32) + 'px'"
            ></iframe>
        @elseif($message['body_text'])
            <pre style="font-family: 'Montserrat', sans-serif; font-size: 0.875rem; line-height: 1.7; color: #374151; white-space: pre-wrap; margin: 0;">{{ $message['body_text'] }}</pre>
        @else
            <p style="color: #9ca3af; font-size: 0.875rem;">(No message body)</p>
        @endif
    </div>

</div>

{{-- Quick reply shortcut --}}
<div style="margin-top: 1rem; text-align: right;">
    <a href="{{ route('admin.inbox.reply', $message['msgno']) }}" class="admin-btn admin-btn-outline">↩ Reply to {{ $message['from_email'] }}</a>
</div>

@endsection
