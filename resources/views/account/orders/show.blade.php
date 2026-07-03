@extends('layouts.app')

@section('title', 'Order #' . $order->id)

@section('content')
<div class="page-container py-12">

    <div class="mb-8 pb-6 border-b border-walnut/20">
        <p class="section-label mb-2">Account</p>
        <h1 class="font-heading text-3xl font-light">Order #{{ $order->id }}</h1>
    </div>

    <div class="flex flex-col lg:flex-row gap-10">

        @include('account.partials.sidebar')

        <div class="flex-1 min-w-0 space-y-8">

            {{-- Back Link + Invoice --}}
            <div class="flex items-center justify-between">
                <a href="{{ route('account.orders') }}" class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-walnut hover:text-charcoal transition-colors">
                    ← All Orders
                </a>
                <a href="{{ route('account.orders.invoice', $order) }}" target="_blank" class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-walnut hover:text-charcoal transition-colors">
                    Print Invoice ↗
                </a>
            </div>

            {{-- Order Header --}}
            <div class="card p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="section-label mb-1">Order Placed</p>
                        <p class="text-sm">{{ $order->created_at->format('F j, Y \a\t g:i A') }}</p>
                    </div>
                    <div>
                        @php
                            $statusMap = [
                                'pending_payment' => ['class' => 'admin-badge-warning', 'label' => 'Pending Payment'],
                                'processing'      => ['class' => 'admin-badge-info',    'label' => 'Processing'],
                                'in_production'   => ['class' => 'admin-badge-info',    'label' => 'In Production'],
                                'shipped'         => ['class' => 'admin-badge-success', 'label' => 'Shipped'],
                                'delivered'       => ['class' => 'admin-badge-success', 'label' => 'Delivered'],
                                'cancelled'       => ['class' => 'admin-badge-error',   'label' => 'Cancelled'],
                                'refunded'        => ['class' => 'admin-badge-error',   'label' => 'Refunded'],
                            ];
                            $badge = $statusMap[$order->status] ?? ['class' => 'admin-badge-neutral', 'label' => ucwords(str_replace('_', ' ', $order->status))];
                        @endphp
                        <p class="section-label mb-1">Status</p>
                        <span class="{{ $badge['class'] }} text-sm px-3 py-1">{{ $badge['label'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Tracking --}}
            @if($order->relationLoaded('shipments') && $order->shipments->isNotEmpty())
                @php $shipment = $order->shipments->first(); @endphp
                @if($shipment->tracking_number)
                    <div class="card p-6">
                        <p class="section-label mb-3">Shipping & Tracking</p>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            @if($shipment->carrier)
                                <span class="text-sm font-semibold uppercase tracking-wide">{{ $shipment->carrier }}</span>
                                <span class="hidden sm:block text-walnut/40">—</span>
                            @endif
                            <span class="font-mono text-sm">{{ $shipment->tracking_number }}</span>
                            @if($shipment->shipped_at)
                                <span class="text-xs text-walnut">Shipped {{ $shipment->shipped_at->format('M j, Y') }}</span>
                            @endif
                            @if($shipment->estimated_delivery)
                                <span class="text-xs text-walnut">Est. delivery {{ $shipment->estimated_delivery->format('M j, Y') }}</span>
                            @endif
                        </div>
                    </div>
                @endif
            @elseif(isset($order->latestShipment) && $order->latestShipment?->tracking_number)
                @php $shipment = $order->latestShipment; @endphp
                <div class="card p-6">
                    <p class="section-label mb-3">Shipping & Tracking</p>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        @if($shipment->carrier)
                            <span class="text-sm font-semibold uppercase tracking-wide">{{ $shipment->carrier }}</span>
                            <span class="hidden sm:block text-walnut/40">—</span>
                        @endif
                        <span class="font-mono text-sm">{{ $shipment->tracking_number }}</span>
                        @if($shipment->shipped_at)
                            <span class="text-xs text-walnut">Shipped {{ $shipment->shipped_at->format('M j, Y') }}</span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Order Items --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-heading text-xl font-light">Items</h2>
                    <form method="POST" action="{{ route('account.orders.reorder', $order) }}">
                        @csrf
                        <button type="submit" class="btn-outline text-xs px-4 py-2">
                            Reorder These Items
                        </button>
                    </form>
                </div>
                <div class="card overflow-hidden">
                    <div class="divide-y divide-walnut/10">
                        @foreach($order->items as $item)
                            <div class="flex gap-4 p-4 sm:p-5">
                                {{-- Image --}}
                                <div class="w-16 h-16 sm:w-20 sm:h-20 shrink-0 overflow-hidden" style="background-color: #EDE8DF;">
                                    @php
                                        $itemImage = $item->variant?->product?->primary_image_url
                                            ?? $item->product?->primary_image_url
                                            ?? null;
                                    @endphp
                                    @if($itemImage)
                                        <img
                                            src="{{ $itemImage }}"
                                            alt="{{ $item->name_snapshot }}"
                                            class="w-full h-full object-cover"
                                        >
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-8 h-8" style="color: #C4B9AA;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- Details --}}
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm leading-snug">{{ $item->name_snapshot }}</p>
                                    @if($item->variant_label_snapshot)
                                        <p class="text-xs text-walnut mt-0.5">{{ $item->variant_label_snapshot }}</p>
                                    @endif
                                    @if($item->personalization_text)
                                        <p class="text-xs text-walnut mt-1 italic">"{{ $item->personalization_text }}"</p>
                                    @endif
                                    <p class="text-xs text-walnut mt-1">Qty: {{ $item->qty }}</p>
                                </div>

                                {{-- Price --}}
                                <div class="text-right shrink-0">
                                    <p class="font-medium text-sm">${{ number_format($item->subtotal, 2) }}</p>
                                    @if($item->qty > 1)
                                        <p class="text-xs text-walnut mt-0.5">${{ number_format($item->price_snapshot, 2) }} each</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Order Totals --}}
            <div class="card p-6">
                <h2 class="font-heading text-xl font-light mb-4">Order Summary</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-walnut">Subtotal</span>
                        <span>${{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    @if($order->discount_amount > 0)
                        <div class="flex justify-between">
                            <span class="text-walnut">
                                Discount
                                @if($order->coupon_code_snapshot)
                                    <span class="font-mono text-xs">({{ $order->coupon_code_snapshot }})</span>
                                @endif
                            </span>
                            <span style="color: #166534;">−${{ number_format($order->discount_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-walnut">Shipping</span>
                        @if($order->shipping_amount > 0)
                            <span>${{ number_format($order->shipping_amount, 2) }}</span>
                        @else
                            <span style="color: #166534;">Free</span>
                        @endif
                    </div>
                    @if($order->tax_amount > 0)
                        <div class="flex justify-between">
                            <span class="text-walnut">Tax</span>
                            <span>${{ number_format($order->tax_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="pt-3 border-t border-walnut/20 flex justify-between font-semibold">
                        <span>Total</span>
                        <span>${{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Shipping Address --}}
            @if($order->shipping_line1)
                <div class="card p-6">
                    <h2 class="font-heading text-xl font-light mb-4">Shipping Address</h2>
                    <address class="text-sm not-italic text-charcoal leading-relaxed">
                        {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
                        {{ $order->shipping_line1 }}<br>
                        @if($order->shipping_line2)
                            {{ $order->shipping_line2 }}<br>
                        @endif
                        {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}<br>
                        {{ $order->shipping_country }}
                    </address>
                </div>
            @endif

            {{-- Status Timeline --}}
            @if($order->relationLoaded('statusHistory') && $order->statusHistory->isNotEmpty())
                <div class="card p-6">
                    <h2 class="font-heading text-xl font-light mb-4">Order Timeline</h2>
                    <ol class="relative border-l border-walnut/30 space-y-6 ml-3">
                        @foreach($order->statusHistory->sortByDesc('created_at') as $history)
                            @php
                                $histBadge = $statusMap[$history->status] ?? ['class' => 'admin-badge-neutral', 'label' => ucwords(str_replace('_', ' ', $history->status))];
                            @endphp
                            <li class="pl-6">
                                <span class="absolute -left-1.5 mt-1 w-3 h-3 rounded-full border-2 border-walnut" style="background-color: #F4F1EA;"></span>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                    <span class="{{ $histBadge['class'] }}">{{ $histBadge['label'] }}</span>
                                    <span class="text-xs text-walnut">{{ $history->created_at->format('M j, Y \a\t g:i A') }}</span>
                                </div>
                                @if($history->note)
                                    <p class="mt-1 text-xs text-walnut">{{ $history->note }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
