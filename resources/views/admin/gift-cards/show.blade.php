@extends('layouts.admin')

@section('page-title', 'Gift Card ' . $giftCard->code)

@section('content')

<div style="max-width: 900px;">

    <div style="margin-bottom: 1rem;">
        <a href="{{ route('admin.gift-cards.index') }}" style="font-size: 0.8125rem; color: #6b7280;">
            &larr; Back to Gift Cards
        </a>
    </div>

    {{-- Summary --}}
    <div class="admin-card" style="margin-bottom: 1.5rem;">
        <div class="admin-card-header">
            <span class="admin-card-title" style="font-family: monospace;">{{ $giftCard->code }}</span>
            @if(! $giftCard->active)
                <span class="admin-badge-neutral">Voided</span>
            @elseif($giftCard->balance <= 0)
                <span class="admin-badge-neutral">Depleted</span>
            @else
                <span class="admin-badge-success">Active</span>
            @endif
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
            <div>
                <div class="admin-label">Current Balance</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #2C4C3B;">${{ number_format($giftCard->balance, 2) }}</div>
            </div>
            <div>
                <div class="admin-label">Initial Balance</div>
                <div style="font-size: 1.125rem; color: #333;">${{ number_format($giftCard->initial_balance, 2) }}</div>
            </div>
            <div>
                <div class="admin-label">Redeemable?</div>
                <div style="font-size: 1.125rem; color: #333;">{{ $giftCard->isRedeemable() ? 'Yes' : 'No' }}</div>
            </div>
            <div>
                <div class="admin-label">Expires</div>
                <div style="font-size: 1.125rem; color: #333;">{{ $giftCard->expires_at ? $giftCard->expires_at->format('M j, Y') : 'Never' }}</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.8125rem; color: #6b7280; border-top: 1px solid #f3f4f6; padding-top: 1rem;">
            <div><strong style="color: #333;">Recipient:</strong> {{ $giftCard->recipient_name ?: '—' }} {{ $giftCard->recipient_email ? '('.$giftCard->recipient_email.')' : '' }}</div>
            <div><strong style="color: #333;">Purchaser:</strong> {{ $giftCard->purchaser_email ?: '—' }}</div>
            <div><strong style="color: #333;">Issued:</strong> {{ $giftCard->created_at->format('M j, Y g:i A') }}</div>
        </div>
        @if($giftCard->message)
        <div style="margin-top: 1rem; font-size: 0.8125rem; color: #6b7280;">
            <strong style="color: #333;">Message:</strong> {{ $giftCard->message }}
        </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="admin-card" style="margin-bottom: 1.5rem;">
        <div class="admin-card-header">
            <span class="admin-card-title">Actions</span>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 2rem;">
            {{-- Void / Reactivate --}}
            <div>
                @if($giftCard->active)
                    @php($partial = $giftCard->balance > 0 && $giftCard->balance < $giftCard->initial_balance)
                    <form method="POST" action="{{ route('admin.gift-cards.void', $giftCard) }}"
                          onsubmit="return confirm('{{ $partial ? 'This card still has $'.number_format($giftCard->balance, 2).' remaining. Void it anyway?' : 'Void this gift card?' }}');">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button type="submit" class="admin-btn admin-btn-danger">Void Gift Card</button>
                    </form>
                    <p class="admin-hint" style="margin-top: 0.375rem;">Deactivates the card so it can no longer be redeemed.</p>
                @else
                    <form method="POST" action="{{ route('admin.gift-cards.reactivate', $giftCard) }}">
                        @csrf
                        <button type="submit" class="admin-btn admin-btn-outline">Reactivate</button>
                    </form>
                    <p class="admin-hint" style="margin-top: 0.375rem;">Re-enables redemption for this card.</p>
                @endif
            </div>

            {{-- Refund to card --}}
            <div>
                <form method="POST" action="{{ route('admin.gift-cards.refund', $giftCard) }}" style="display: flex; align-items: flex-end; gap: 0.5rem;">
                    @csrf
                    <div>
                        <label class="admin-label" for="refund_amount">Credit Amount</label>
                        <div style="position: relative; width: 140px;">
                            <span style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 0.875rem; pointer-events: none;">$</span>
                            <input type="number" id="refund_amount" name="amount" class="admin-input" step="0.01" min="0.01" placeholder="0.00" style="padding-left: 1.75rem;" required>
                        </div>
                    </div>
                    <button type="submit" class="admin-btn admin-btn-outline">Add to Balance</button>
                </form>
                <p class="admin-hint" style="margin-top: 0.375rem;">Credits store value back onto the card.</p>
            </div>
        </div>
    </div>

    {{-- Transaction history --}}
    <div class="admin-card" style="padding: 0;">
        <div class="admin-card-header" style="padding: 1rem 1.5rem;">
            <span class="admin-card-title">Transaction History</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Order</th>
                        <th style="text-align: right;">Amount</th>
                        <th style="text-align: right;">Balance After</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($giftCard->transactions as $txn)
                    <tr>
                        <td style="font-size: 0.8125rem; color: #6b7280; white-space: nowrap;">{{ $txn->created_at->format('M j, Y g:i A') }}</td>
                        <td style="font-size: 0.8125rem; text-transform: capitalize;">{{ $txn->type }}</td>
                        <td style="font-size: 0.8125rem;">
                            @if($txn->order)
                                <a href="{{ route('admin.orders.show', $txn->order) }}" style="color: #2C4C3B; font-weight: 600;">#{{ $txn->order->id }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td style="text-align: right; font-weight: 600; font-family: monospace; color: {{ $txn->amount < 0 ? '#991b1b' : '#166534' }};">
                            {{ $txn->amount < 0 ? '-' : '+' }}${{ number_format(abs($txn->amount), 2) }}
                        </td>
                        <td style="text-align: right; font-family: monospace; color: #333;">${{ number_format($txn->balance_after, 2) }}</td>
                        <td style="font-size: 0.8125rem; color: #6b7280;">{{ $txn->note }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #9ca3af; padding: 2rem;">No transactions recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection
