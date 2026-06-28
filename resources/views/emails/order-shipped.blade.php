@extends('layouts.email')

@section('subject', 'Your order #' . $order->id . ' is on its way!')

@section('content')

<h1>Your order has shipped!</h1>

<p>Hi {{ $order->shipping_first_name }},</p>

<p>Exciting news — your Timber Trace Crafts order is on its way to you!</p>

@if($shipment)
<div style="background: #F4F1EA; border: 1px solid #E8E2D9; padding: 20px; margin: 24px 0;">
    <p style="margin: 0 0 8px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #8C7B6C;">Shipment Details</p>
    <p style="margin: 0 0 4px;"><strong>Carrier:</strong> {{ $shipment->carrier }}</p>
    <p style="margin: 0 0 4px;"><strong>Tracking #:</strong> {{ $shipment->tracking_number }}</p>
    @if($shipment->estimated_delivery)
        <p style="margin: 0;"><strong>Estimated Delivery:</strong> {{ \Carbon\Carbon::parse($shipment->estimated_delivery)->format('F j, Y') }}</p>
    @endif
</div>
@endif

<p>
    <strong>Order #{{ $order->id }}</strong><br>
    Shipping to: {{ $order->shipping_first_name }} {{ $order->shipping_last_name }},
    {{ $order->shipping_city }}, {{ $order->shipping_state }}
</p>

<hr class="email-divider">

<p style="text-align: center; margin-top: 28px;">
    @if($order->user_id)
        <a href="{{ url('/account/orders/' . $order->id) }}" class="email-btn email-btn-forest">View Order</a>
    @else
        <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('order.status.view', ['order' => $order->id]) }}" class="email-btn email-btn-forest">Track Order</a>
    @endif
</p>

<p style="font-size: 13px; color: #555; margin-top: 24px; line-height: 1.7;">
    We put a lot of care into every piece. We hope you love it! If anything isn't right,
    please <a href="{{ url('/contact') }}">contact us</a> and we'll make it right.
</p>

@endsection
