@extends('layouts.email')

@section('subject', 'Update on your order #' . $order->id)

@section('content')

@php
$statusMessages = [
    'processing'    => ['title' => 'Your order is being processed',    'body' => 'We\'ve received your order and it\'s in the queue. We\'ll begin crafting it shortly.'],
    'in_production' => ['title' => 'Your order is in production',      'body' => 'We\'ve started crafting your order in our studio. We\'ll let you know when it ships.'],
    'shipped'       => ['title' => 'Your order has shipped',           'body' => 'Great news — your order is on its way!'],
    'delivered'     => ['title' => 'Your order has been delivered',    'body' => 'Your order has been marked as delivered. We hope you love it!'],
    'refunded'      => ['title' => 'Your order has been refunded',     'body' => 'Your refund has been processed. It may take 3–5 business days to appear on your statement.'],
    'cancelled'     => ['title' => 'Your order has been cancelled',    'body' => 'Your order has been cancelled. If you have questions, please contact us.'],
];
$msg = $statusMessages[$order->status] ?? ['title' => 'Your order has been updated', 'body' => 'There\'s been an update on your order.'];
@endphp

<h1>{{ $msg['title'] }}</h1>

<p>Hi {{ $order->shipping_first_name }},</p>

<p>{{ $msg['body'] }}</p>

<p>
    <strong>Order #{{ $order->id }}</strong><br>
    Placed on {{ $order->created_at->format('F j, Y') }}<br>
    Total: <strong>${{ number_format($order->total, 2) }}</strong>
</p>

<hr class="email-divider">

<p style="text-align: center; margin-top: 28px;">
    @if($order->user_id)
        <a href="{{ url('/account/orders/' . $order->id) }}" class="email-btn email-btn-forest">View Order</a>
    @else
        <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('order.status.view', ['order' => $order->id]) }}" class="email-btn email-btn-forest">Track Order</a>
    @endif
</p>

<p style="font-size: 12px; color: #8C7B6C; margin-top: 24px;">
    Questions? Reply to this email or visit our <a href="{{ url('/contact') }}">contact page</a>.
</p>

@endsection
