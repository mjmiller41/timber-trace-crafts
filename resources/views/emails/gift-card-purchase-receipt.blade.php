@extends('layouts.email')

@section('subject', 'Your Timber Trace Crafts gift card purchase')

@section('content')

<h1>Thank you for your purchase!</h1>

<p>
    Your Timber Trace Crafts gift card is on its way to
    <strong>{{ $giftCard->recipient_email }}</strong>@if($giftCard->recipient_name) ({{ $giftCard->recipient_name }})@endif.
</p>

<hr class="email-divider">

<table class="email-table">
    <tbody>
        <tr>
            <td>Gift-card value</td>
            <td style="text-align: right;"><strong>${{ number_format($giftCard->initial_balance, 2) }}</strong></td>
        </tr>
        <tr>
            <td>Code</td>
            <td style="text-align: right;">{{ $giftCard->code }}</td>
        </tr>
        <tr>
            <td>Recipient</td>
            <td style="text-align: right;">{{ $giftCard->recipient_email }}</td>
        </tr>
    </tbody>
</table>

@if($giftCard->message)
<p style="margin-top: 16px;"><strong>Your message:</strong></p>
<p style="font-style: italic; color: #5C4B3C;">&ldquo;{{ $giftCard->message }}&rdquo;</p>
@endif

<p style="margin-top: 24px;">
    The recipient will receive their code by email with instructions to redeem it at checkout.
    Thank you for supporting handmade woodwork.
</p>

<p style="text-align: center; margin-top: 24px;">
    <a href="{{ route('home') }}" class="email-btn email-btn-forest">Visit Timber Trace Crafts</a>
</p>

@endsection
