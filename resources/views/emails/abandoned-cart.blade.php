@extends('layouts.email')

@section('subject', 'You left something behind — Timber Trace Crafts')

@section('content')

<h1>{{ $stage >= 2 ? 'Your cart is still waiting' : 'You left something behind' }}</h1>

<p>
    Hi there,<br><br>
    @if($stage >= 2)
        Just a friendly last reminder — the handcrafted item{{ count($items) === 1 ? '' : 's' }} below
        {{ count($items) === 1 ? 'is' : 'are' }} still in your cart. Each piece is made to order, so grab
        yours before it slips your mind.
    @else
        We noticed you didn't quite finish checking out. Your cart is saved and ready whenever you are.
    @endif
</p>

<hr class="email-divider">

<table class="email-table">
    <thead>
        <tr>
            <th>Item</th>
            <th style="text-align: right;">Qty</th>
            <th style="text-align: right;">Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>
                <strong>{{ $item['name'] ?? 'Item' }}</strong>
                @if(!empty($item['variant_label']))
                    <br><span style="color: #8C7B6C; font-size: 12px;">{{ $item['variant_label'] }}</span>
                @endif
                @if(!empty($item['personalization_text']))
                    <br><span style="color: #8C7B6C; font-size: 12px; font-style: italic;">"{{ $item['personalization_text'] }}"</span>
                @endif
            </td>
            <td style="text-align: right;">{{ $item['qty'] ?? 1 }}</td>
            <td style="text-align: right;">${{ number_format((($item['price'] ?? 0) + ($item['personalization_price'] ?? 0)) * ($item['qty'] ?? 1), 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2">Subtotal</td>
            <td style="text-align: right;">${{ number_format($subtotal, 2) }}</td>
        </tr>
    </tfoot>
</table>

<p style="text-align: center; margin: 32px 0 8px;">
    <a href="{{ $cartUrl }}" class="email-btn email-btn-forest">Return to your cart</a>
</p>

<hr class="email-divider">

<p style="font-size: 11px; color: #8C7B6C; text-align: center;">
    You're receiving this because a cart was started with this email address at timbertracecrafts.com.
    <br>
    <a href="{{ $unsubscribeUrl }}" style="color: #8C7B6C;">Unsubscribe from cart reminders</a>
</p>

@endsection
