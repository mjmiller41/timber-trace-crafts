@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')

{{-- Stats Row --}}
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">

    <div class="admin-stat-card">
        <div class="admin-stat-label">Today's Revenue</div>
        <div class="admin-stat-value">${{ number_format($todayRevenue, 2) }}</div>
    </div>

    <div class="admin-stat-card">
        <div class="admin-stat-label">This Week</div>
        <div class="admin-stat-value">${{ number_format($weekRevenue, 2) }}</div>
    </div>

    <div class="admin-stat-card">
        <div class="admin-stat-label">This Month</div>
        <div class="admin-stat-value">${{ number_format($monthRevenue, 2) }}</div>
    </div>

    <div class="admin-stat-card">
        <div class="admin-stat-label">Total Orders</div>
        <div class="admin-stat-value">{{ array_sum($orderCounts) }}</div>
    </div>

</div>

{{-- Alert Row --}}
@if($lowStockVariants->count() > 0 || $pendingReviews > 0 || $unreadMessages > 0)
<div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1.5rem; padding: 0.875rem 1.25rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 0.25rem;">
    <span style="font-size: 0.8125rem; font-weight: 600; color: #92400e; margin-right: 0.25rem;">Attention needed:</span>

    @if($lowStockVariants->count() > 0)
        <a href="{{ route('admin.products.index') }}" style="display: inline-flex; align-items: center; gap: 0.375rem;">
            <span class="admin-badge-warning">{{ $lowStockVariants->count() }} low stock</span>
        </a>
    @endif

    @if($pendingReviews > 0)
        <a href="{{ route('admin.reviews.index') }}" style="display: inline-flex; align-items: center; gap: 0.375rem;">
            <span class="admin-badge-warning">{{ $pendingReviews }} pending {{ Str::plural('review', $pendingReviews) }}</span>
        </a>
    @endif

    @if($unreadMessages > 0)
        <a href="{{ route('admin.messages.index') }}" style="display: inline-flex; align-items: center; gap: 0.375rem;">
            <span class="admin-badge-info">{{ $unreadMessages }} unread {{ Str::plural('message', $unreadMessages) }}</span>
        </a>
    @endif
</div>
@endif

{{-- Order Status Breakdown --}}
<div style="margin-bottom: 1.5rem;">
    <h2 style="font-family: 'Playfair Display', serif; font-size: 1rem; font-weight: 400; color: #333; margin-bottom: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.75rem; font-weight: 600; color: #8C7B6C;">Order Status Breakdown</h2>
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.75rem;">

        @php
            $statusConfig = [
                'pending_payment' => ['label' => 'Pending Payment', 'badge' => 'admin-badge-warning'],
                'processing'      => ['label' => 'Processing',      'badge' => 'admin-badge-info'],
                'in_production'   => ['label' => 'In Production',   'badge' => 'admin-badge-info'],
                'shipped'         => ['label' => 'Shipped',         'badge' => 'admin-badge-success'],
                'delivered'       => ['label' => 'Delivered',       'badge' => 'admin-badge-success'],
            ];
        @endphp

        @foreach($statusConfig as $key => $cfg)
        <a href="{{ route('admin.orders.index', ['status' => $key]) }}" style="text-decoration: none;">
            <div class="admin-stat-card" style="padding: 1rem; text-align: center; transition: border-color 0.15s;" onmouseover="this.style.borderColor='#8C7B6C'" onmouseout="this.style.borderColor='#e5e7eb'">
                <div style="font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 300; color: #333; margin-bottom: 0.375rem;">
                    {{ $orderCounts[$key] ?? 0 }}
                </div>
                <span class="{{ $cfg['badge'] }}" style="font-size: 0.6875rem;">{{ $cfg['label'] }}</span>
            </div>
        </a>
        @endforeach

    </div>
</div>

{{-- Two-column lower section --}}
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">

    {{-- Recent Orders Table --}}
    <div class="admin-card" style="padding: 0;">
        <div class="admin-card-header" style="padding: 1.25rem 1.5rem; margin-bottom: 0;">
            <span class="admin-card-title">Recent Orders</span>
            <a href="{{ route('admin.orders.index') }}" class="admin-btn admin-btn-outline" style="font-size: 0.75rem;">View All</a>
        </div>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th style="text-align: right;">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" style="font-weight: 600; color: #2C4C3B;">#{{ $order->id }}</a>
                        </td>
                        <td>
                            @if($order->user)
                                {{ $order->user->name }}
                            @else
                                <span style="color: #6b7280;">{{ $order->guest_email ?? 'Guest' }}</span>
                            @endif
                        </td>
                        <td style="color: #6b7280; font-size: 0.8125rem;">
                            {{ $order->created_at->format('M j, Y') }}
                        </td>
                        <td>
                            @php
                                $badge = match($order->status) {
                                    'delivered'       => 'admin-badge-success',
                                    'shipped'         => 'admin-badge-success',
                                    'processing', 'in_production' => 'admin-badge-info',
                                    'pending_payment' => 'admin-badge-warning',
                                    'cancelled'       => 'admin-badge-error',
                                    default           => 'admin-badge-neutral',
                                };
                                $label = ucwords(str_replace('_', ' ', $order->status));
                            @endphp
                            <span class="{{ $badge }}">{{ $label }}</span>
                        </td>
                        <td style="text-align: right; font-weight: 600;">${{ number_format($order->total, 2) }}</td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #9ca3af; padding: 2rem;">No orders yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Low Stock Alerts --}}
    <div class="admin-card" style="padding: 0;">
        <div class="admin-card-header" style="padding: 1.25rem 1.5rem; margin-bottom: 0;">
            <span class="admin-card-title">Low Stock</span>
            @if($lowStockVariants->count() > 0)
                <span class="admin-badge-warning">{{ $lowStockVariants->count() }}</span>
            @endif
        </div>
        <div style="padding: 0.5rem 0;">
            @forelse($lowStockVariants as $variant)
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.625rem 1.5rem; border-bottom: 1px solid #f3f4f6;">
                <div style="min-width: 0; flex: 1;">
                    <div style="font-size: 0.8125rem; font-weight: 600; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $variant->product->name ?? 'Unknown Product' }}
                    </div>
                    <div style="font-size: 0.75rem; color: #6b7280;">{{ $variant->label }}</div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0; margin-left: 0.75rem;">
                    <span style="font-size: 0.8125rem; font-weight: 600; color: {{ $variant->stock_qty === 0 ? '#991b1b' : '#92400e' }};">
                        {{ $variant->stock_qty }} left
                    </span>
                    @if($variant->product)
                    <a href="{{ route('admin.products.edit', $variant->product) }}" class="admin-btn admin-btn-outline" style="font-size: 0.6875rem; padding: 0.2rem 0.5rem;">Edit</a>
                    @endif
                </div>
            </div>
            @empty
            <div style="text-align: center; color: #9ca3af; padding: 2rem 1.5rem; font-size: 0.875rem;">
                All variants are well stocked.
            </div>
            @endforelse
        </div>
    </div>

</div>

@endsection
