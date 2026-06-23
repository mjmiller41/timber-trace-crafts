@extends('layouts.print')

@section('title', 'Packing Slip — Order #' . $order->id)

@section('content')

{{-- Header --}}
<div class="print-header">
    <div style="display: flex; align-items: center; gap: 12px;">
        <img src="{{ $siteLogoUrl ?? asset('images/logo.png') }}" alt="" style="height: 48px; width: auto; object-fit: contain;">
        <div>
            <div class="print-logo">{{ $siteName ?? 'Timber Trace Crafts' }}</div>
            <div class="print-logo-sub">{{ $siteTagline ?? '' }}</div>
        </div>
    </div>
    <div class="print-doc-title">
        <h1>Packing Slip</h1>
        <div class="doc-meta">Order Date: {{ $order->created_at->format('F j, Y') }}</div>
        <div class="doc-meta" style="font-size: 15px; font-weight: 700; margin-top: 4px;">Order #{{ $order->id }}</div>
    </div>
</div>

{{-- Ship To --}}
<div class="print-addresses">
    <div class="print-address-block">
        <div class="print-section-label">Ship To</div>
        <address>
            <strong>{{ $order->shipping_first_name }} {{ $order->shipping_last_name }}</strong><br>
            {{ $order->shipping_line1 }}
            @if($order->shipping_line2)
                <br>{{ $order->shipping_line2 }}
            @endif
            <br>{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}
            @if($order->shipping_country && $order->shipping_country !== 'US')
                <br>{{ $order->shipping_country }}
            @endif
            @if($order->shipping_phone)
                <br>{{ $order->shipping_phone }}
            @endif
        </address>
    </div>

    @if($order->shipments->isNotEmpty())
    <div class="print-address-block">
        <div class="print-section-label">Shipping Info</div>
        @php $shipment = $order->shipments->first(); @endphp
        <address>
            @if($shipment->carrier)
                <strong>{{ $shipment->carrier }}</strong>
                @if($shipment->service) — {{ $shipment->service }}@endif
                <br>
            @endif
            @if($shipment->tracking_number)
                Tracking: {{ $shipment->tracking_number }}
            @endif
        </address>
    </div>
    @endif
</div>

{{-- Items Table --}}
<div class="print-section-label">Items</div>
<table class="print-table">
    <thead>
        <tr>
            <th>Product</th>
            <th>Variant</th>
            <th>Personalization</th>
            <th style="text-align: right;">Qty</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
        <tr>
            <td>
                <div class="product-name">{{ $item->name_snapshot }}</div>
                @if($item->sku_snapshot)
                    <div class="product-variant">SKU: {{ $item->sku_snapshot }}</div>
                @endif
            </td>
            <td>
                @if($item->variant_label_snapshot)
                    {{ $item->variant_label_snapshot }}
                @else
                    <span style="color: #aaa;">—</span>
                @endif
            </td>
            <td>
                @if($item->personalization_text)
                    <em style="color: #2C4C3B;">{{ $item->personalization_text }}</em>
                @else
                    <span style="color: #aaa;">—</span>
                @endif
            </td>
            <td style="text-align: right; font-weight: 700;">{{ $item->qty }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Gift Message --}}
@if($order->gift_message)
<div class="print-notes" style="border-left: 3px solid #2C4C3B; margin-top: 8px;">
    <div class="print-notes-label">&#x1F381; Gift Message</div>
    <p style="font-style: italic; color: #333; font-size: 12px; line-height: 1.6; margin: 0;">{{ $order->gift_message }}</p>
</div>
@endif

{{-- Footer --}}
<div class="print-footer">
    <p style="margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em;">Thank you for your order!</p>
    <p>Questions? Contact us at <strong>hello@timbertracecrafts.com</strong></p>
    <p style="margin-top: 8px;">{{ $siteName ?? 'Timber Trace Crafts' }} &mdash; {{ $siteTagline ?? '' }}</p>
</div>

@endsection
