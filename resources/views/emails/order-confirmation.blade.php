@extends('layouts.email')

@section('subject', 'Order #' . $order->id . ' Confirmed — Timber Trace Crafts')

@section('content')

<h1>Order Confirmed!</h1>

<p>
    Hi {{ $order->shipping_first_name }},<br><br>
    Thank you for your order! We've received it and are getting to work. Here's a summary of what you ordered.
</p>

<hr class="email-divider">

<h2>Order #{{ $order->id }}</h2>

<table class="email-table">
    <thead>
        <tr>
            <th>Item</th>
            <th style="text-align: right;">Qty</th>
            <th style="text-align: right;">Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
        <tr>
            <td>
                <strong>{{ $item->name_snapshot }}</strong>
                @if($item->variant_label_snapshot)
                    <br><span style="color: #8C7B6C; font-size: 12px;">{{ $item->variant_label_snapshot }}</span>
                @endif
                @if($item->personalization_text)
                    <br><span style="color: #8C7B6C; font-size: 12px; font-style: italic;">"{{ $item->personalization_text }}"</span>
                @endif
            </td>
            <td style="text-align: right;">{{ $item->qty }}</td>
            <td style="text-align: right;">${{ number_format($item->subtotal, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2">Subtotal</td>
            <td style="text-align: right;">${{ number_format($order->subtotal, 2) }}</td>
        </tr>
        @if($order->discount_amount > 0)
        <tr>
            <td colspan="2" style="color: #2C4C3B;">Discount</td>
            <td style="text-align: right; color: #2C4C3B;">−${{ number_format($order->discount_amount, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td colspan="2">Shipping ({{ $order->shipping_method }})</td>
            <td style="text-align: right;">
                {{ $order->shipping_amount == 0 ? 'Free' : '$' . number_format($order->shipping_amount, 2) }}
            </td>
        </tr>
        <tr>
            <td colspan="2">Tax</td>
            <td style="text-align: right;">${{ number_format($order->tax_amount, 2) }}</td>
        </tr>
        <tr style="font-size: 15px;">
            <td colspan="2"><strong>Total</strong></td>
            <td style="text-align: right;"><strong>${{ number_format($order->total, 2) }}</strong></td>
        </tr>
    </tfoot>
</table>

<hr class="email-divider">

<h2>Shipping To</h2>
<p>
    {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
    {{ $order->shipping_line1 }}
    @if($order->shipping_line2), {{ $order->shipping_line2 }}@endif<br>
    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}<br>
    United States
</p>

@if($order->gift_message)
<hr class="email-divider">
<p><strong>Gift Message:</strong><br>{{ $order->gift_message }}</p>
@endif

<hr class="email-divider">

<p>We'll notify you by email as soon as your order ships. Most orders ship within 2–4 business days.</p>

<p style="text-align: center; margin-top: 32px;">
    @auth
        <a href="{{ url('/account/orders/' . $order->id) }}" class="email-btn email-btn-forest">View Order Details</a>
    @else
        <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('order.status.view', ['order' => $order->id]) }}" class="email-btn email-btn-forest">Track Your Order</a>
    @endauth
</p>

<p style="font-size: 12px; color: #8C7B6C;">
    Questions? Reply to this email or visit our <a href="{{ url('/contact') }}">contact page</a>.
</p>

@endsection
