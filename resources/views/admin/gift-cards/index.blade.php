@extends('layouts.admin')

@section('page-title', 'Gift Cards')

@section('content')

<div class="admin-card" style="margin-bottom: 1.5rem; padding: 1rem 1.5rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
        <form method="GET" action="{{ route('admin.gift-cards.index') }}" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                placeholder="Search code, recipient or purchaser email"
                class="admin-input"
                style="min-width: 280px;"
            >
            <select name="status" class="admin-input" style="width: auto;">
                <option value="">All statuses</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active (has balance)</option>
                <option value="depleted" {{ $status === 'depleted' ? 'selected' : '' }}>Depleted</option>
                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Voided / Inactive</option>
                <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
            <button type="submit" class="admin-btn admin-btn-outline">Search</button>
            @if($search !== '' || $status !== '')
                <a href="{{ route('admin.gift-cards.index') }}" style="font-size: 0.8125rem; color: #6b7280;">Clear</a>
            @endif
        </form>
        <a href="{{ route('admin.gift-cards.create') }}" class="admin-btn" style="background-color: #2C4C3B; color: #fff;">
            + Issue Gift Card
        </a>
    </div>
</div>

<div class="admin-card" style="padding: 0;">
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Recipient</th>
                    <th>Balance</th>
                    <th>Initial</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($giftCards as $card)
                <tr>
                    <td>
                        <a href="{{ route('admin.gift-cards.show', $card) }}" style="font-family: monospace; font-weight: 600; font-size: 0.9375rem; color: #1E3529;">{{ $card->code }}</a>
                    </td>
                    <td style="font-size: 0.8125rem; color: #6b7280;">
                        @if($card->recipient_email || $card->recipient_name)
                            <div>{{ $card->recipient_name ?: '—' }}</div>
                            @if($card->recipient_email)
                                <div style="font-size: 0.75rem;">{{ $card->recipient_email }}</div>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td style="font-weight: 600;">${{ number_format($card->balance, 2) }}</td>
                    <td style="font-size: 0.8125rem; color: #6b7280;">${{ number_format($card->initial_balance, 2) }}</td>
                    <td style="font-size: 0.8125rem; color: #6b7280; white-space: nowrap;">
                        {{ $card->expires_at ? $card->expires_at->format('M j, Y') : '—' }}
                        @if($card->expires_at && $card->expires_at->isPast())
                            <span class="admin-badge-error" style="font-size: 0.6875rem; margin-left: 0.25rem;">Expired</span>
                        @endif
                    </td>
                    <td>
                        @if(! $card->active)
                            <span class="admin-badge-neutral">Voided</span>
                        @elseif($card->balance <= 0)
                            <span class="admin-badge-neutral">Depleted</span>
                        @else
                            <span class="admin-badge-success">Active</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.gift-cards.show', $card) }}" class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #9ca3af; padding: 3rem;">
                        No gift cards found. <a href="{{ route('admin.gift-cards.create') }}" style="color: #2C4C3B;">Issue one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($giftCards->hasPages())
    <div style="padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6;">
        {{ $giftCards->links() }}
    </div>
    @endif
</div>

@endsection
