@extends('layouts.email')

@section('subject', 'Your Timber Trace Crafts gift card')

@section('content')

<h1>You've received a gift card!</h1>

<p>
    Hi{{ $giftCard->recipient_name ? ' ' . $giftCard->recipient_name : '' }},<br><br>
    Someone thoughtful has sent you a Timber Trace Crafts gift card — handmade, heirloom-quality
    woodwork awaits.
</p>

@if($giftCard->message)
<table class="email-table" style="margin: 20px 0;">
    <tbody>
        <tr>
            <td style="font-style: italic; color: #5C4B3C;">
                &ldquo;{{ $giftCard->message }}&rdquo;
            </td>
        </tr>
    </tbody>
</table>
@endif

<hr class="email-divider">

<p style="text-align: center; margin: 24px 0 8px;">Your gift-card code</p>

<p style="text-align: center; font-size: 26px; letter-spacing: 3px; font-weight: 700; color: #2C4C3B; margin: 0 0 8px;">
    {{ $giftCard->code }}
</p>

<p style="text-align: center; font-size: 18px; margin: 0 0 24px;">
    Balance: <strong>${{ number_format($giftCard->balance, 2) }}</strong>
</p>

<p style="text-align: center;">
    <a href="{{ route('shop') }}" class="email-btn email-btn-forest">Start Shopping</a>
</p>

<p style="margin-top: 24px;">
    <strong>How to redeem:</strong> Add items to your cart, then enter the code above in the
    &ldquo;Gift card&rdquo; field at checkout. Any unused balance stays on the card for next time.
</p>

<p style="color: #8C7B6C; font-size: 13px;">
    Questions? Just reply to this email — we're happy to help.
</p>

@endsection
