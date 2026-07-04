@extends('layouts.admin')

@section('page-title', 'Audit Log')

@section('content')

{{-- Filters --}}
<div class="admin-card" style="margin-bottom: 1.5rem; padding: 1rem 1.5rem;">
    <form method="GET" action="{{ route('admin.audit.index') }}" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 220px;">
            <label class="admin-label" for="q">Search</label>
            <input type="text" id="q" name="q" class="admin-input" placeholder="Email, route, path or subject&hellip;" value="{{ request('q') }}">
        </div>
        <div style="min-width: 140px;">
            <label class="admin-label" for="method">Method</label>
            <select id="method" name="method" class="admin-input">
                <option value="">All</option>
                @foreach($methods as $m)
                    <option value="{{ $m }}" @selected(request('method') === $m)>{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <div style="display: flex; gap: 0.5rem; padding-bottom: 0.125rem;">
            <button type="submit" class="admin-btn admin-btn-primary">Filter</button>
            @if(request('q') || request('method'))
                <a href="{{ route('admin.audit.index') }}" class="admin-btn admin-btn-outline">Clear</a>
            @endif
        </div>
        <div style="margin-left: auto; padding-bottom: 0.125rem;">
            <span style="font-size: 0.8125rem; color: #6b7280;">{{ number_format($logs->total()) }} {{ Str::plural('event', $logs->total()) }}</span>
        </div>
    </form>
</div>

<div class="admin-card" style="padding: 0;">
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Admin</th>
                    <th>Method</th>
                    <th>Action</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="color: #6b7280; font-size: 0.8125rem; white-space: nowrap;" title="{{ $log->created_at }}">
                        {{ $log->created_at?->format('M j, Y g:i A') }}
                    </td>
                    <td style="font-size: 0.875rem;">
                        {{ $log->user?->name ?? $log->actor_email ?? '—' }}
                        @if($log->actor_email)
                            <div style="color: #9ca3af; font-size: 0.75rem;">{{ $log->actor_email }}</div>
                        @endif
                    </td>
                    <td>
                        @php $mc = match($log->method) {
                            'DELETE' => 'admin-badge-error',
                            'POST' => 'admin-badge-success',
                            default => 'admin-badge-neutral',
                        }; @endphp
                        <span class="{{ $mc }}" style="font-size: 0.6875rem;">{{ $log->method }}</span>
                    </td>
                    <td style="font-size: 0.8125rem; font-family: ui-monospace, monospace;">{{ $log->label() }}</td>
                    <td style="font-size: 0.8125rem; color: #374151;">
                        @if($log->subject_type)
                            {{ $log->subject_type }} #{{ $log->subject_id }}
                        @else
                            <span style="color: #9ca3af;">—</span>
                        @endif
                    </td>
                    <td style="font-size: 0.8125rem; color: {{ $log->status_code >= 400 ? '#b91c1c' : '#6b7280' }};">{{ $log->status_code ?? '—' }}</td>
                    <td style="font-size: 0.75rem; color: #9ca3af; font-family: ui-monospace, monospace;">{{ $log->ip_address ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align: center; color: #9ca3af; padding: 2rem;">No audit events recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top: 1.25rem;">
    {{ $logs->appends(request()->query())->links() }}
</div>

@endsection
