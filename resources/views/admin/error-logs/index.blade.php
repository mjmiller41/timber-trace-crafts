@extends('layouts.admin')

@section('page-title', 'Error Log')

@section('content')

@php
    $levelColor = fn ($lvl) => match ($lvl) {
        'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => '#b91c1c',
        'WARNING' => '#b45309',
        'NOTICE', 'INFO' => '#2563eb',
        default => '#6b7280',
    };
@endphp

<div class="admin-card" style="margin-bottom: 1.5rem; padding: 1rem 1.5rem;">
    <form method="GET" action="{{ route('admin.errors.index') }}" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
        <div style="min-width: 240px;">
            <label class="admin-label" for="file">Log file</label>
            <select id="file" name="file" class="admin-input" onchange="this.form.submit()">
                @forelse($files as $f)
                    <option value="{{ $f }}" @selected($f === $current)>{{ $f }}</option>
                @empty
                    <option value="">No log files</option>
                @endforelse
            </select>
        </div>
        <div style="min-width: 160px;">
            <label class="admin-label" for="level">Level</label>
            <select id="level" name="level" class="admin-input" onchange="this.form.submit()">
                <option value="">All levels</option>
                @foreach($levels as $lvl)
                    <option value="{{ $lvl }}" @selected($lvl === $level)>{{ ucfirst(strtolower($lvl)) }}</option>
                @endforeach
            </select>
        </div>
        <div style="display: flex; gap: 0.5rem; padding-bottom: 0.125rem;">
            <button type="submit" class="admin-btn admin-btn-primary">Apply</button>
            @if($level)
                <a href="{{ route('admin.errors.index', ['file' => $current]) }}" class="admin-btn admin-btn-outline">Clear</a>
            @endif
        </div>
        <div style="margin-left: auto; padding-bottom: 0.125rem;">
            <span style="font-size: 0.8125rem; color: #6b7280;">Showing latest {{ count($entries) }} {{ Str::plural('entry', count($entries)) }}</span>
        </div>
    </form>
</div>

@if(empty($entries))
    <div class="admin-card" style="text-align: center; color: #9ca3af; padding: 2.5rem;">
        No log entries{{ $level ? ' at this level' : '' }}.
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
        @foreach($entries as $entry)
            <details class="admin-card" style="padding: 0.75rem 1rem;">
                <summary style="cursor: pointer; display: flex; align-items: baseline; gap: 0.75rem; list-style: none;">
                    <span style="font-weight: 700; font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; color: {{ $levelColor($entry['level']) }}; min-width: 78px;">{{ $entry['level'] }}</span>
                    <span style="color: #6b7280; font-size: 0.75rem; font-family: ui-monospace, monospace; white-space: nowrap;">{{ $entry['timestamp'] }}</span>
                    <span style="font-size: 0.8125rem; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ Str::limit(strtok($entry['message'], "\n"), 160) }}</span>
                </summary>
                <pre style="margin-top: 0.75rem; white-space: pre-wrap; word-break: break-word; font-size: 0.75rem; line-height: 1.5; color: #374151; background: #f9fafb; padding: 0.75rem; border-radius: 6px; overflow-x: auto;">{{ $entry['message'] }}</pre>
            </details>
        @endforeach
    </div>
@endif

@endsection
