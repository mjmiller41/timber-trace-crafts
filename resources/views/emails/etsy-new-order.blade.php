@extends('layouts.email')

@section('subject', 'New Etsy Order #' . $order->id)

@section('content')

<h1>New Etsy Order Received</h1>

<p>
    A new order just came in via Etsy. Here's the summary.
</p>

<hr class="email-divider">

<h2>Order #{{ $order->id }} &mdash; ${{ number_format($order->total, 2) }}</h2>

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
                @if($item->personalization_text)
                    <br><span style="color: #8C7B6C; font-size: 12px; font-style: italic;">Personalization: "{{ $item->personalization_text }}"</span>
                @endif
            </td>
            <td style="text-align: right;">{{ $item->qty }}</td>
            <td style="text-align: right;">${{ number_format($item->subtotal, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2"><strong>Total</strong></td>
            <td style="text-align: right;"><strong>${{ number_format($order->total, 2) }}</strong></td>
        </tr>
    </tfoot>
</table>

<hr class="email-divider">

<h2>Ship To</h2>
<p>
    {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
    {{ $order->shipping_line1 }}
    @if($order->shipping_line2), {{ $order->shipping_line2 }}@endif<br>
    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}<br>
    {{ $order->shipping_country }}
</p>

@if($order->message_from_buyer)
<hr class="email-divider">
<p><strong>Message from buyer:</strong><br>{{ $order->message_from_buyer }}</p>
@endif

<p style="text-align: center; margin-top: 32px;">
    <a href="{{ url('/admin/orders/' . $order->id) }}" class="email-btn email-btn-forest">View Order in Admin</a>
</p>

@endsection
