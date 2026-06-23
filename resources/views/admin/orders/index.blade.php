@extends('layouts.admin')

@section('page-title', 'Orders')

@section('content')

{{-- Filter Bar --}}
<div class="admin-card" style="margin-bottom: 1.5rem; padding: 1rem 1.5rem;">
    <form method="GET" action="{{ route('admin.orders.index') }}" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">

        <div style="flex: 1; min-width: 200px;">
            <label class="admin-label" for="search">Search</label>
            <input
                type="text"
                id="search"
                name="search"
                class="admin-input"
                placeholder="Order #, customer name or email…"
                value="{{ request('search') }}"
            >
        </div>

        <div style="min-width: 160px;">
            <label class="admin-label" for="status">Status</label>
            <select id="status" name="status" class="admin-input">
                <option value="">All Statuses</option>
                <option value="pending_payment" @selected(request('status') === 'pending_payment')>Pending Payment</option>
                <option value="processing"      @selected(request('status') === 'processing')>Processing</option>
                <option value="in_production"   @selected(request('status') === 'in_production')>In Production</option>
                <option value="shipped"         @selected(request('status') === 'shipped')>Shipped</option>
                <option value="delivered"       @selected(request('status') === 'delivered')>Delivered</option>
                <option value="cancelled"       @selected(request('status') === 'cancelled')>Cancelled</option>
                <option value="refunded"        @selected(request('status') === 'refunded')>Refunded</option>
            </select>
        </div>

        <div style="min-width: 140px;">
            <label class="admin-label" for="date_from">From</label>
            <input type="date" id="date_from" name="date_from" class="admin-input" value="{{ request('date_from') }}">
        </div>

        <div style="min-width: 140px;">
            <label class="admin-label" for="date_to">To</label>
            <input type="date" id="date_to" name="date_to" class="admin-input" value="{{ request('date_to') }}">
        </div>

        <div style="display: flex; gap: 0.5rem; padding-bottom: 0.125rem;">
            <button type="submit" class="admin-btn admin-btn-primary">Filter</button>
            @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                <a href="{{ route('admin.orders.index') }}" class="admin-btn admin-btn-outline">Clear</a>
            @endif
        </div>

        <div style="margin-left: auto; padding-bottom: 0.125rem;">
            <button type="button" class="admin-btn admin-btn-outline" disabled title="Export coming soon">
                &#x2B07; Export CSV
            </button>
        </div>

    </form>
</div>

{{-- Bulk Actions + Table --}}
<div class="admin-card" style="padding: 0;"
     x-data="{ selected: [], allChecked: false }">

    {{-- Bulk action bar --}}
    <div style="padding: 0.875rem 1.5rem; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 0.75rem;">
        <span style="font-size: 0.8125rem; color: #6b7280;" x-show="selected.length === 0">
            {{ $orders->total() }} {{ Str::plural('order', $orders->total()) }}
        </span>
        <span style="font-size: 0.8125rem; color: #333; font-weight: 600;" x-show="selected.length > 0" x-text="selected.length + ' selected'"></span>

        <div x-show="selected.length > 0" style="display: flex; align-items: center; gap: 0.5rem; margin-left: auto;">
            <form method="POST" action="{{ route('admin.orders.index') }}" style="display: flex; gap: 0.5rem; align-items: center;">
                @csrf
                @method('PATCH')
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="order_ids[]" :value="id">
                </template>
                <select name="bulk_status" class="admin-input" style="width: auto; font-size: 0.8125rem; padding: 0.375rem 0.625rem;">
                    <option value="">Change status to…</option>
                    <option value="processing">Processing</option>
                    <option value="in_production">In Production</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <button type="submit" class="admin-btn admin-btn-primary" style="font-size: 0.8125rem;">Apply</button>
            </form>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 2.5rem; padding-right: 0;">
                        <input
                            type="checkbox"
                            x-model="allChecked"
                            @change="selected = allChecked ? [{{ $orders->pluck('id')->implode(',') }}].filter(Boolean) : []"
                            style="cursor: pointer;"
                        >
                    </th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th style="text-align: right;">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td style="padding-right: 0;">
                        <input
                            type="checkbox"
                            :value="{{ $order->id }}"
                            x-model="selected"
                            style="cursor: pointer;"
                        >
                    </td>
                    <td>
                        <a href="{{ route('admin.orders.show', $order) }}" style="font-weight: 600; color: #2C4C3B;">
                            #{{ $order->id }}
                        </a>
                    </td>
                    <td>
                        <div style="font-weight: 500;">
                            @if($order->user)
                                {{ $order->user->name }}
                            @else
                                <span class="admin-badge-neutral" style="font-size: 0.6875rem;">Guest</span>
                            @endif
                        </div>
                        <div style="font-size: 0.75rem; color: #6b7280;">
                            {{ $order->user?->email ?? $order->guest_email }}
                        </div>
                    </td>
                    <td style="color: #6b7280; font-size: 0.8125rem; white-space: nowrap;">
                        {{ $order->created_at->format('M j, Y') }}<br>
                        <span style="font-size: 0.75rem;">{{ $order->created_at->format('g:i A') }}</span>
                    </td>
                    <td style="color: #6b7280;">
                        {{ $order->items_count ?? $order->items->count() }} {{ Str::plural('item', $order->items_count ?? $order->items->count()) }}
                    </td>
                    <td>
                        @php
                            $badge = match($order->status) {
                                'delivered'               => 'admin-badge-success',
                                'shipped'                 => 'admin-badge-success',
                                'processing', 'in_production' => 'admin-badge-info',
                                'pending_payment'         => 'admin-badge-warning',
                                'cancelled', 'refunded'   => 'admin-badge-error',
                                default                   => 'admin-badge-neutral',
                            };
                            $label = ucwords(str_replace('_', ' ', $order->status));
                        @endphp
                        <span class="{{ $badge }}">{{ $label }}</span>
                    </td>
                    <td style="text-align: right; font-weight: 600; white-space: nowrap;">
                        ${{ number_format($order->total, 2) }}
                    </td>
                    <td>
                        <a href="{{ route('admin.orders.show', $order) }}" class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem; white-space: nowrap;">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #9ca3af; padding: 3rem;">
                        No orders found.
                        @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                            <a href="{{ route('admin.orders.index') }}" style="color: #2C4C3B; margin-left: 0.375rem;">Clear filters</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($orders->hasPages())
    <div style="padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6;">
        {{ $orders->appends(request()->query())->links() }}
    </div>
    @endif

</div>

@endsection
