@extends('layouts.admin')

@push('title')Inbox @endpush

@section('content')

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-family: 'Playfair Display', serif; font-weight: 300; font-size: 1.75rem; color: #2d2d2d; margin: 0;">Inbox</h1>
        <p style="font-size: 0.8125rem; color: #9ca3af; margin: 0.25rem 0 0;">
            @if($unread > 0)
                <span style="color: #2C4C3B; font-weight: 500;">{{ $unread }} unread &middot;</span>
            @endif
            Checked at {{ now()->format('g:i A') }}
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="{{ route('admin.inbox.index') }}" class="admin-btn admin-btn-outline">&#x21BB; Check for mail</a>
        <a href="{{ route('admin.inbox.compose') }}" class="admin-btn admin-btn-primary">Compose</a>
    </div>
</div>

@if($error)
    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 0.875rem 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 0.875rem;">
        Could not connect to inbox: {{ $error }}
    </div>
@endif

@if(session('success'))
    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; padding: 0.875rem 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 0.875rem;">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 0.875rem 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 0.875rem;">
        {{ session('error') }}
    </div>
@endif

<div style="background: white; border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden;">
    @forelse($messages as $msg)
        <a
            href="{{ route('admin.inbox.show', $msg['msgno']) }}"
            style="display: flex; align-items: flex-start; gap: 1rem; padding: 0.875rem 1rem; border-bottom: 1px solid #f3f4f6; text-decoration: none; color: inherit; transition: background 0.1s; {{ ! $msg['seen'] ? 'background: #f8fffe;' : '' }}"
            onmouseover="this.style.background='#f9fafb'"
            onmouseout="this.style.background='{{ ! $msg['seen'] ? '#f8fffe' : 'white' }}'"
        >
            {{-- Unread dot --}}
            <div style="width: 8px; height: 8px; border-radius: 50%; background: {{ ! $msg['seen'] ? '#2C4C3B' : 'transparent' }}; margin-top: 0.4rem; flex-shrink: 0;"></div>

            {{-- Content --}}
            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 1rem;">
                    <span style="font-size: 0.875rem; font-weight: {{ ! $msg['seen'] ? '600' : '400' }}; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">
                        {{ $msg['from'] ?: $msg['from_email'] }}
                    </span>
                    <span style="font-size: 0.75rem; color: #9ca3af; white-space: nowrap; flex-shrink: 0;">
                        {{ $msg['date']->diffForHumans() }}
                    </span>
                </div>
                <div style="font-size: 0.8125rem; color: {{ ! $msg['seen'] ? '#111827' : '#6b7280' }}; font-weight: {{ ! $msg['seen'] ? '500' : '400' }}; margin-top: 0.125rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ $msg['subject'] }}
                </div>
            </div>

            @if($msg['answered'])
                <span style="font-size: 0.6875rem; color: #9ca3af; flex-shrink: 0; margin-top: 0.25rem;">↩ replied</span>
            @endif
        </a>
    @empty
        @if(!$error)
            <div style="padding: 3rem; text-align: center; color: #9ca3af; font-size: 0.875rem;">
                Your inbox is empty.
            </div>
        @endif
    @endforelse
</div>

{{-- Pagination --}}
@if($total > 25)
    <div style="display: flex; gap: 0.5rem; justify-content: center; margin-top: 1.5rem;">
        @if($page > 1)
            <a href="{{ route('admin.inbox.index', ['page' => $page - 1]) }}" class="admin-btn admin-btn-outline">← Newer</a>
        @endif
        @if($page * 25 < $total)
            <a href="{{ route('admin.inbox.index', ['page' => $page + 1]) }}" class="admin-btn admin-btn-outline">Older →</a>
        @endif
    </div>
@endif

@endsection
