@extends('layouts.admin')

@section('page-title', 'Customer')

@section('content')

<div style="margin-bottom: 1rem;">
    <a href="{{ route('admin.customers.index') }}" style="font-size: 0.8125rem; color: #6b7280;">
        &larr; Back to Customers
    </a>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: start;">

    {{-- Left column --}}
    <div>

        {{-- Customer Info --}}
        <div class="admin-card" style="margin-bottom: 1.5rem;">
            <div style="text-align: center; padding-bottom: 1.25rem; border-bottom: 1px solid #f3f4f6; margin-bottom: 1.25rem;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background-color: #2C4C3B; display: flex; align-items: center; justify-content: center; color: #F4F1EA; font-size: 1.25rem; font-weight: 700; margin: 0 auto 0.75rem; font-family: 'Montserrat', sans-serif;">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}{{ strtoupper(substr(strstr($customer->name, ' '), 1, 1)) }}
                </div>
                <h2 style="font-family: 'Playfair Display', serif; font-size: 1.25rem; font-weight: 400; color: #333; margin-bottom: 0.25rem;">{{ $customer->name }}</h2>
                <span class="admin-badge-info">Customer</span>
            </div>

            <dl style="font-size: 0.875rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.625rem;">
                    <dt style="color: #6b7280;">Email</dt>
                    <dd style="font-weight: 500; text-align: right; word-break: break-all;">{{ $customer->email }}</dd>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.625rem;">
                    <dt style="color: #6b7280;">Joined</dt>
                    <dd style="font-weight: 500;">{{ $customer->created_at->format('M j, Y') }}</dd>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.625rem;">
                    <dt style="color: #6b7280;">Orders</dt>
                    <dd style="font-weight: 500;">{{ $customer->orders->count() }}</dd>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <dt style="color: #6b7280;">Total Spent</dt>
                    <dd style="font-weight: 600; color: #2C4C3B;">
                        ${{ number_format($customer->orders->sum('total'), 2) }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Addresses --}}
        <div class="admin-card">
            <div class="admin-card-header" style="margin-bottom: 1rem; padding-bottom: 0.875rem;">
                <span class="admin-card-title" style="font-size: 1rem;">Saved Addresses</span>
            </div>

            @forelse($customer->addresses as $address)
            <div style="padding: 0.875rem; background: #f9fafb; border-radius: 0.25rem; margin-bottom: 0.75rem; font-size: 0.8125rem; line-height: 1.6;">
                @if($address->label)
                    <div style="font-weight: 600; text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.08em; color: #8C7B6C; margin-bottom: 0.25rem;">{{ $address->label }}</div>
                @endif
                <div>{{ $address->first_name }} {{ $address->last_name }}</div>
                <div>{{ $address->line1 }}</div>
                @if($address->line2)<div>{{ $address->line2 }}</div>@endif
                <div>{{ $address->city }}, {{ $address->state }} {{ $address->zip }}</div>
                <div>{{ $address->country }}</div>
                @if($address->phone)<div style="color: #6b7280;">{{ $address->phone }}</div>@endif
            </div>
            @empty
            <p style="font-size: 0.875rem; color: #9ca3af; text-align: center; padding: 1.5rem 0;">No saved addresses.</p>
            @endforelse
        </div>

    </div>

    {{-- Right column --}}
    <div>

        {{-- Orders --}}
        <div class="admin-card" style="padding: 0; margin-bottom: 1.5rem;">
            <div class="admin-card-header" style="padding: 1.25rem 1.5rem; margin-bottom: 0;">
                <span class="admin-card-title">Orders</span>
                <a href="{{ route('admin.orders.index') }}" class="admin-btn admin-btn-outline" style="font-size: 0.75rem;">View All Orders</a>
            </div>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th style="text-align: right;">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customer->orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" style="font-weight: 600; color: #2C4C3B;">#{{ $order->id }}</a>
                            </td>
                            <td style="color: #6b7280; font-size: 0.8125rem; white-space: nowrap;">
                                {{ $order->created_at->format('M j, Y') }}
                            </td>
                            <td>
                                @php
                                    $badge = match($order->status) {
                                        'delivered'       => 'admin-badge-success',
                                        'shipped'         => 'admin-badge-success',
                                        'processing', 'in_production' => 'admin-badge-info',
                                        'pending_payment' => 'admin-badge-warning',
                                        'cancelled', 'refunded' => 'admin-badge-error',
                                        default           => 'admin-badge-neutral',
                                    };
                                @endphp
                                <span class="{{ $badge }}">{{ ucwords(str_replace('_', ' ', $order->status)) }}</span>
                            </td>
                            <td style="text-align: right; font-weight: 600;">${{ number_format($order->total, 2) }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: #9ca3af; padding: 2rem;">No orders yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Reviews --}}
        <div class="admin-card" style="padding: 0;">
            <div class="admin-card-header" style="padding: 1.25rem 1.5rem; margin-bottom: 0;">
                <span class="admin-card-title">Reviews</span>
            </div>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Rating</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customer->reviews as $review)
                        <tr>
                            <td style="font-size: 0.8125rem;">
                                {{ $review->product->name ?? '—' }}
                            </td>
                            <td style="color: #F59E0B; font-size: 0.875rem; white-space: nowrap;">
                                @for($i = 1; $i <= 5; $i++)
                                    {{ $i <= $review->rating ? '★' : '☆' }}
                                @endfor
                            </td>
                            <td style="font-size: 0.8125rem;">{{ $review->title ?? '—' }}</td>
                            <td>
                                @php
                                    $badge = match($review->status) {
                                        'approved' => 'admin-badge-success',
                                        'rejected' => 'admin-badge-error',
                                        default    => 'admin-badge-warning',
                                    };
                                @endphp
                                <span class="{{ $badge }}">{{ ucfirst($review->status) }}</span>
                            </td>
                            <td style="font-size: 0.8125rem; color: #6b7280; white-space: nowrap;">
                                {{ $review->created_at->format('M j, Y') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: #9ca3af; padding: 2rem;">No reviews.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

@endsection
