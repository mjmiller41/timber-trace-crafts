@extends('layouts.admin')

@section('page-title', 'Order #' . $order->id)

@section('content')

{{-- Page header --}}
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
    <div>
        <a href="{{ route('admin.orders.index') }}" style="font-size: 0.8125rem; color: #8C7B6C; display: inline-flex; align-items: center; gap: 0.25rem; margin-bottom: 0.375rem;">
            &#8592; Back to Orders
        </a>
        <h1 style="font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 300; color: #333;">
            Order #{{ $order->id }}
        </h1>
        <div style="font-size: 0.8125rem; color: #6b7280; margin-top: 0.25rem;">
            Placed {{ $order->created_at->format('F j, Y \a\t g:i A') }}
        </div>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="{{ route('admin.orders.packing-slip', $order) }}" target="_blank" class="admin-btn admin-btn-outline">
            &#x1F4C4; Packing Slip
        </a>
        <button type="button" class="admin-btn admin-btn-outline" disabled title="Coming soon">
            &#9993; Send Status Email
        </button>
    </div>
</div>

{{-- Two-column layout --}}
<div style="display: grid; grid-template-columns: 1fr 320px; gap: 1.5rem; align-items: start;">

    {{-- LEFT: Main Order Details --}}
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- Order Items --}}
        <div class="admin-card" style="padding: 0;">
            <div class="admin-card-header" style="padding: 1.25rem 1.5rem; margin-bottom: 0;">
                <span class="admin-card-title">Items</span>
                <span style="font-size: 0.8125rem; color: #6b7280;">{{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}</span>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 60px;"></th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th style="text-align: right;">Price</th>
                        <th style="text-align: right;">Qty</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            @php
                                $imgUrl = $item->product?->primary_image_url ?? null;
                            @endphp
                            @if($imgUrl)
                                <img src="{{ $imgUrl }}" alt="{{ $item->name_snapshot }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 0.125rem; border: 1px solid #e5e7eb;">
                            @else
                                <div style="width: 50px; height: 50px; background: #f3f4f6; border-radius: 0.125rem; display: flex; align-items: center; justify-content: center; color: #d1d5db; font-size: 1.25rem;">&#x1F4F7;</div>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $item->name_snapshot }}</div>
                            @if($item->variant_label_snapshot)
                                <div style="font-size: 0.75rem; color: #6b7280;">{{ $item->variant_label_snapshot }}</div>
                            @endif
                            @if($item->personalization_text)
                                <div style="font-size: 0.75rem; color: #2C4C3B; margin-top: 0.25rem; background: #f0fdf4; padding: 0.25rem 0.5rem; border-radius: 0.125rem; border-left: 2px solid #2C4C3B;">
                                    <span style="font-weight: 600;">Personalization:</span> {{ $item->personalization_text }}
                                </div>
                            @endif
                        </td>
                        <td style="font-size: 0.8125rem; color: #6b7280; font-family: monospace;">{{ $item->sku_snapshot }}</td>
                        <td style="text-align: right;">${{ number_format($item->price_snapshot, 2) }}</td>
                        <td style="text-align: right;">{{ $item->qty }}</td>
                        <td style="text-align: right; font-weight: 600;">${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totals --}}
            <div style="padding: 1.25rem 1.5rem; border-top: 1px solid #f3f4f6;">
                <div style="max-width: 280px; margin-left: auto;">
                    <div style="display: flex; justify-content: space-between; padding: 0.375rem 0; font-size: 0.875rem; color: #6b7280;">
                        <span>Subtotal</span>
                        <span>${{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    @if($order->discount_amount > 0)
                    <div style="display: flex; justify-content: space-between; padding: 0.375rem 0; font-size: 0.875rem; color: #059669;">
                        <span>
                            Discount
                            @if($order->coupon_code_snapshot)
                                <span style="font-size: 0.75rem; background: #dcfce7; padding: 0.125rem 0.375rem; border-radius: 9999px; font-family: monospace;">{{ $order->coupon_code_snapshot }}</span>
                            @endif
                        </span>
                        <span>−${{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                    @endif
                    <div style="display: flex; justify-content: space-between; padding: 0.375rem 0; font-size: 0.875rem; color: #6b7280;">
                        <span>
                            Shipping
                            @if($order->shipping_method)
                                <span style="font-size: 0.75rem; color: #9ca3af;">({{ $order->shipping_method }})</span>
                            @endif
                        </span>
                        <span>
                            @if((float)$order->shipping_amount === 0.0)
                                <span style="color: #059669;">Free</span>
                            @else
                                ${{ number_format($order->shipping_amount, 2) }}
                            @endif
                        </span>
                    </div>
                    @if($order->tax_amount > 0)
                    <div style="display: flex; justify-content: space-between; padding: 0.375rem 0; font-size: 0.875rem; color: #6b7280;">
                        <span>Tax</span>
                        <span>${{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                    @endif
                    <div style="display: flex; justify-content: space-between; padding: 0.75rem 0 0; font-size: 1rem; font-weight: 700; color: #333; border-top: 2px solid #333; margin-top: 0.375rem;">
                        <span>Total</span>
                        <span>${{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Order Timeline --}}
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="admin-card-title">Order Timeline</span>
            </div>
            @forelse($order->statusHistory->sortByDesc('created_at') as $entry)
            <div style="display: flex; gap: 1rem; padding: 0.875rem 0; {{ !$loop->last ? 'border-bottom: 1px solid #f3f4f6;' : '' }}">
                {{-- Icon --}}
                <div style="flex-shrink: 0; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.875rem;
                    {{ match($entry->status) {
                        'delivered'       => 'background: #dcfce7; color: #166534;',
                        'shipped'         => 'background: #dbeafe; color: #1e40af;',
                        'processing', 'in_production' => 'background: #dbeafe; color: #1e40af;',
                        'pending_payment' => 'background: #fef9c3; color: #854d0e;',
                        'cancelled', 'refunded' => 'background: #fee2e2; color: #991b1b;',
                        default           => 'background: #f3f4f6; color: #374151;',
                    } }}">
                    {{ match($entry->status) {
                        'delivered'     => '✓',
                        'shipped'       => '→',
                        'cancelled'     => '✕',
                        'refunded'      => '↩',
                        default         => '●',
                    } }}
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        @php
                            $badge = match($entry->status) {
                                'delivered'               => 'admin-badge-success',
                                'shipped'                 => 'admin-badge-success',
                                'processing', 'in_production' => 'admin-badge-info',
                                'pending_payment'         => 'admin-badge-warning',
                                'cancelled', 'refunded'   => 'admin-badge-error',
                                default                   => 'admin-badge-neutral',
                            };
                        @endphp
                        <span class="{{ $badge }}">{{ ucwords(str_replace('_', ' ', $entry->status)) }}</span>
                        <span style="font-size: 0.75rem; color: #9ca3af;">{{ $entry->created_at->format('M j, Y g:i A') }}</span>
                        @if($entry->creator)
                            <span style="font-size: 0.75rem; color: #9ca3af;">by {{ $entry->creator->name }}</span>
                        @endif
                    </div>
                    @if($entry->note)
                        <p style="font-size: 0.8125rem; color: #6b7280; margin-top: 0.25rem;">{{ $entry->note }}</p>
                    @endif
                </div>
            </div>
            @empty
            <p style="color: #9ca3af; font-size: 0.875rem;">No status history recorded.</p>
            @endforelse
        </div>

    </div>

    {{-- RIGHT: Sidebar --}}
    <div style="display: flex; flex-direction: column; gap: 1rem;">

        {{-- Status Update --}}
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="admin-card-title">Update Status</span>
                @php
                    $badge = match($order->status) {
                        'delivered'               => 'admin-badge-success',
                        'shipped'                 => 'admin-badge-success',
                        'processing', 'in_production' => 'admin-badge-info',
                        'pending_payment'         => 'admin-badge-warning',
                        'cancelled', 'refunded'   => 'admin-badge-error',
                        default                   => 'admin-badge-neutral',
                    };
                @endphp
                <span class="{{ $badge }}">{{ ucwords(str_replace('_', ' ', $order->status)) }}</span>
            </div>
            <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                @csrf
                @method('PATCH')
                <div style="margin-bottom: 0.875rem;">
                    <label class="admin-label" for="status_update">New Status</label>
                    <select id="status_update" name="status" class="admin-input">
                        <option value="pending_payment" @selected($order->status === 'pending_payment')>Pending Payment</option>
                        <option value="processing"      @selected($order->status === 'processing')>Processing</option>
                        <option value="in_production"   @selected($order->status === 'in_production')>In Production</option>
                        <option value="shipped"         @selected($order->status === 'shipped')>Shipped</option>
                        <option value="delivered"       @selected($order->status === 'delivered')>Delivered</option>
                        <option value="cancelled"       @selected($order->status === 'cancelled')>Cancelled</option>
                        <option value="refunded"        @selected($order->status === 'refunded')>Refunded</option>
                    </select>
                </div>
                <div style="margin-bottom: 0.875rem;">
                    <label class="admin-label" for="status_note">Note (optional)</label>
                    <textarea id="status_note" name="note" class="admin-input" rows="3" placeholder="Internal note about this status change…" style="resize: vertical;"></textarea>
                </div>
                <button type="submit" class="admin-btn admin-btn-primary" style="width: 100%; justify-content: center;">Update Status</button>
            </form>
        </div>

        {{-- Shipment --}}
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="admin-card-title">Shipments</span>
            </div>

            @if($order->shipments->isEmpty())
            <form method="POST" action="{{ route('admin.orders.shipment', $order) }}">
                @csrf
                <div style="margin-bottom: 0.75rem;">
                    <label class="admin-label" for="carrier">Carrier</label>
                    <input type="text" id="carrier" name="carrier" class="admin-input" placeholder="e.g. UPS, FedEx, USPS">
                </div>
                <div style="margin-bottom: 0.75rem;">
                    <label class="admin-label" for="service">Service</label>
                    <input type="text" id="service" name="service" class="admin-input" placeholder="e.g. Ground, Priority Mail">
                </div>
                <div style="margin-bottom: 0.75rem;">
                    <label class="admin-label" for="tracking_number">Tracking Number</label>
                    <input type="text" id="tracking_number" name="tracking_number" class="admin-input" placeholder="1Z999AA10123456784">
                </div>
                <div style="margin-bottom: 0.875rem;">
                    <label class="admin-label" for="shipped_at">Shipped At</label>
                    <input type="datetime-local" id="shipped_at" name="shipped_at" class="admin-input" value="{{ now()->format('Y-m-d\TH:i') }}">
                </div>
                <button type="submit" class="admin-btn admin-btn-primary" style="width: 100%; justify-content: center;">Add Tracking</button>
            </form>
            @else
            @foreach($order->shipments as $shipment)
            <div style="{{ !$loop->first ? 'border-top: 1px solid #f3f4f6; margin-top: 0.75rem; padding-top: 0.75rem;' : '' }}">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.375rem;">
                    <span style="font-size: 0.8125rem; font-weight: 600; color: #333;">{{ $shipment->carrier }}</span>
                    <span style="font-size: 0.75rem; color: #6b7280;">{{ optional($shipment->shipped_at)->format('M j, Y') }}</span>
                </div>
                @if($shipment->service)
                    <div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">{{ $shipment->service }}</div>
                @endif
                @if($shipment->tracking_number)
                    <div style="font-size: 0.8125rem; font-family: monospace; background: #f9fafb; padding: 0.375rem 0.625rem; border-radius: 0.125rem; border: 1px solid #e5e7eb; word-break: break-all;">
                        {{ $shipment->tracking_number }}
                    </div>
                @endif
            </div>
            @endforeach
            @endif
        </div>

        {{-- Customer --}}
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="admin-card-title">Customer</span>
                @if($order->isGuest())
                    <span class="admin-badge-neutral" style="font-size: 0.6875rem;">Guest</span>
                @endif
            </div>
            @if($order->user)
            <div style="font-size: 0.875rem;">
                <div style="font-weight: 600; margin-bottom: 0.25rem;">{{ $order->user->name }}</div>
                <div style="color: #6b7280; margin-bottom: 0.625rem;">{{ $order->user->email }}</div>
                <a href="{{ route('admin.customers.show', $order->user) }}" class="admin-btn admin-btn-outline" style="font-size: 0.75rem;">View Profile</a>
            </div>
            @else
            <div style="font-size: 0.875rem; color: #6b7280;">
                {{ $order->guest_email ?? 'No email on record' }}
            </div>
            @endif
        </div>

        {{-- Shipping Address --}}
        <div class="admin-card">
            <div class="admin-card-header" style="margin-bottom: 0.75rem;">
                <span class="admin-card-title">Ship To</span>
            </div>
            <address style="font-style: normal; font-size: 0.875rem; line-height: 1.75; color: #333;">
                {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
                {{ $order->shipping_line1 }}
                @if($order->shipping_line2)
                    <br>{{ $order->shipping_line2 }}
                @endif
                <br>{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}
                @if($order->shipping_country && $order->shipping_country !== 'US')
                    <br>{{ $order->shipping_country }}
                @endif
                @if($order->shipping_phone)
                    <br><span style="color: #6b7280;">{{ $order->shipping_phone }}</span>
                @endif
            </address>
        </div>

        {{-- Gift Message --}}
        @if($order->gift_message)
        <div class="admin-card">
            <div class="admin-card-header" style="margin-bottom: 0.75rem;">
                <span class="admin-card-title">Gift Message</span>
                <span style="font-size: 1rem;">&#x1F381;</span>
            </div>
            <blockquote style="border-left: 3px solid #2C4C3B; padding: 0.5rem 0.875rem; margin: 0; font-size: 0.875rem; color: #333; font-style: italic; background: #f0fdf4; border-radius: 0 0.125rem 0.125rem 0;">
                {{ $order->gift_message }}
            </blockquote>
        </div>
        @endif

        {{-- Internal Notes --}}
        @if($order->notes)
        <div class="admin-card">
            <div class="admin-card-header" style="margin-bottom: 0.75rem;">
                <span class="admin-card-title">Internal Notes</span>
            </div>
            <p style="font-size: 0.875rem; color: #6b7280; line-height: 1.6;">{{ $order->notes }}</p>
        </div>
        @endif

    </div>
    {{-- end right sidebar --}}

</div>

@endsection
