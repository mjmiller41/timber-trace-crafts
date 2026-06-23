@extends('layouts.print')

@section('title', 'Invoice #' . $order->id)

@section('content')

    {{-- Header --}}
    <div class="print-header">
        <div style="display: flex; align-items: center; gap: 12px;">
            <img src="{{ $siteLogoUrl }}" alt="" style="height: 48px; width: auto; object-fit: contain;">
            <div>
                <div class="print-logo">{{ $siteName }}</div>
                <div class="print-logo-sub">{{ $siteTagline }}</div>
            </div>
        </div>
        <div class="print-doc-title">
            <h1>Invoice</h1>
            <div class="doc-meta">Order #{{ $order->id }}</div>
            <div class="doc-meta">{{ $order->created_at->format('F j, Y') }}</div>
        </div>
    </div>

    {{-- Addresses --}}
    <div class="print-addresses">
        <div class="print-address-block">
            <div class="print-section-label">From</div>
            <address>
                Timber Trace Crafts<br>
                Avon Park, FL 33825<br>
                timbertracerafts@gmail.com
            </address>
        </div>

        @if($order->shipping_line1)
            <div class="print-address-block">
                <div class="print-section-label">Bill / Ship To</div>
                <address>
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
    </div>

    {{-- Items table --}}
    <table class="print-table">
        <thead>
            <tr>
                <th style="width:45%">Item</th>
                <th>Wood / Variant</th>
                <th style="text-align:center;">Qty</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        <div class="product-name">{{ $item->name_snapshot }}</div>
                        @if($item->personalization_text)
                            <div class="product-variant">"{{ $item->personalization_text }}"</div>
                        @endif
                    </td>
                    <td>{{ $item->variant_label_snapshot ?? '—' }}</td>
                    <td style="text-align:center;">{{ $item->qty }}</td>
                    <td>${{ number_format($item->price_snapshot, 2) }}</td>
                    <td>${{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="print-totals">
        <div class="print-totals-inner">
            <div class="print-totals-row">
                <span>Subtotal</span>
                <span>${{ number_format($order->subtotal, 2) }}</span>
            </div>
            @if($order->discount_amount > 0)
                <div class="print-totals-row">
                    <span>
                        Discount
                        @if($order->coupon_code_snapshot)
                            ({{ $order->coupon_code_snapshot }})
                        @endif
                    </span>
                    <span>−${{ number_format($order->discount_amount, 2) }}</span>
                </div>
            @endif
            <div class="print-totals-row">
                <span>Shipping</span>
                <span>{{ $order->shipping_amount > 0 ? '$'.number_format($order->shipping_amount, 2) : 'Free' }}</span>
            </div>
            @if($order->tax_amount > 0)
                <div class="print-totals-row">
                    <span>Tax</span>
                    <span>${{ number_format($order->tax_amount, 2) }}</span>
                </div>
            @endif
            <div class="print-totals-row total">
                <span>Total</span>
                <span>${{ number_format($order->total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Gift message --}}
    @if($order->gift_message)
        <div class="print-notes">
            <div class="print-notes-label">Gift Message</div>
            {{ $order->gift_message }}
        </div>
    @endif

    {{-- Footer --}}
    <div class="print-footer">
        Thank you for your order! · {{ $siteName }} · Avon Park, FL
    </div>

@endsection
