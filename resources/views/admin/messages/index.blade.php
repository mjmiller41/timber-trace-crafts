@extends('layouts.admin')

@section('page-title', 'Contact Messages')

@section('content')

{{-- Status Filter Tabs --}}
<div style="display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 1.5rem;">
    @php
        $tabs = [
            ''        => 'All',
            'unread'  => 'New',
            'read'    => 'Read',
            'replied' => 'Replied',
        ];
        $activeTab = request('status', '');
    @endphp
    @foreach($tabs as $value => $label)
    <a
        href="{{ route('admin.messages.index', array_filter(['status' => $value])) }}"
        style="padding: 0.625rem 1.25rem; font-size: 0.8125rem; font-weight: 600; text-decoration: none; border-bottom: 2px solid {{ $activeTab === $value ? '#2C4C3B' : 'transparent' }}; margin-bottom: -2px; color: {{ $activeTab === $value ? '#2C4C3B' : '#6b7280' }}; transition: color 0.15s;"
    >
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- Messages list --}}
@forelse($submissions as $submission)
<div
    class="admin-card"
    style="margin-bottom: 1rem; padding: 0;"
    x-data="{ open: false }"
>
    {{-- Summary row --}}
    <div
        style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; cursor: pointer; {{ $submission->status === 'unread' ? 'background-color: #fffbeb;' : '' }}"
        @click="open = !open"
    >
        <div style="flex: 0 0 auto; width: 10px; height: 10px; border-radius: 50%; background-color: {{ $submission->status === 'unread' ? '#F59E0B' : '#e5e7eb' }};"></div>

        <div style="flex: 1; min-width: 0;">
            <div style="display: flex; align-items: baseline; gap: 0.75rem; flex-wrap: wrap;">
                <span style="font-weight: {{ $submission->status === 'unread' ? '700' : '600' }}; font-size: 0.9375rem;">{{ $submission->name }}</span>
                <span style="font-size: 0.8125rem; color: #6b7280;">{{ $submission->email }}</span>
                <span style="font-size: 0.8125rem; color: #333;">{{ $submission->subject ?? '(no subject)' }}</span>
            </div>
            <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.125rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ Str::limit($submission->message, 80) }}
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
            @php
                $badge = match($submission->status) {
                    'unread'  => 'admin-badge-warning',
                    'read'    => 'admin-badge-neutral',
                    'replied' => 'admin-badge-success',
                    default   => 'admin-badge-neutral',
                };
                $statusLabel = match($submission->status) {
                    'unread'  => 'New',
                    'read'    => 'Read',
                    'replied' => 'Replied',
                    default   => ucfirst($submission->status),
                };
            @endphp
            <span class="{{ $badge }}">{{ $statusLabel }}</span>
            <span style="font-size: 0.75rem; color: #6b7280; white-space: nowrap;">{{ $submission->created_at->format('M j, Y') }}</span>
            <span style="font-size: 0.8125rem; color: #6b7280;" x-text="open ? '▲' : '▼'"></span>
        </div>
    </div>

    {{-- Expanded detail --}}
    <div x-show="open" style="display: none; border-top: 1px solid #e5e7eb; padding: 1.25rem;">

        <div style="background: #f9fafb; padding: 1.25rem; border-radius: 0.25rem; margin-bottom: 1.25rem; font-size: 0.9375rem; line-height: 1.75; white-space: pre-wrap; color: #333;">{{ $submission->message }}</div>

        @if($submission->admin_note)
        <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 0.875rem 1rem; border-radius: 0.25rem; margin-bottom: 1.25rem; font-size: 0.8125rem;">
            <strong style="color: #92400e;">Admin note:</strong>
            <div style="margin-top: 0.25rem; color: #78350f;">{{ $submission->admin_note }}</div>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.messages.status', $submission) }}" @click.stop>
            @csrf
            @method('PATCH')

            <div style="margin-bottom: 1rem;">
                <label class="admin-label" for="admin_note_{{ $submission->id }}">Admin Note</label>
                <textarea
                    id="admin_note_{{ $submission->id }}"
                    name="admin_note"
                    class="admin-input"
                    rows="2"
                    placeholder="Internal note (not visible to customer)&hellip;"
                >{{ $submission->admin_note }}</textarea>
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                @if($submission->status !== 'read')
                <button type="submit" name="status" value="read" class="admin-btn admin-btn-outline" style="font-size: 0.8125rem;">Mark as Read</button>
                @endif
                @if($submission->status !== 'replied')
                <button type="submit" name="status" value="replied" class="admin-btn admin-btn-primary" style="font-size: 0.8125rem;">Mark as Replied</button>
                @endif
                @if($submission->status !== 'unread')
                <button type="submit" name="status" value="unread" class="admin-btn admin-btn-outline" style="font-size: 0.8125rem; color: #6b7280;">Mark as Unread</button>
                @endif
                <a href="mailto:{{ $submission->email }}?subject=Re: {{ urlencode($submission->subject ?? 'Your message') }}" class="admin-btn admin-btn-outline" style="font-size: 0.8125rem; margin-left: auto;">
                    Reply by Email &rarr;
                </a>
            </div>
        </form>
    </div>
</div>
@empty
<div class="admin-card" style="text-align: center; color: #9ca3af; padding: 3rem;">
    No messages found
    @if($activeTab)
        with status "{{ $activeTab }}".
        <a href="{{ route('admin.messages.index') }}" style="color: #2C4C3B; margin-left: 0.375rem;">View all</a>
    @else
        .
    @endif
</div>
@endforelse

@if($submissions->hasPages())
<div style="padding: 1rem 0;">
    {{ $submissions->appends(request()->query())->links() }}
</div>
@endif

@endsection
