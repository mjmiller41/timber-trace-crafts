@extends('layouts.email')

@section('subject', $variant->product->name . ' is back in stock!')

@section('content')

<h1>Good news — it's back!</h1>

<p>The item you requested is back in stock and ready to order:</p>

<div style="background: #F4F1EA; border: 1px solid #E8E2D9; padding: 20px; margin: 24px 0;">
    <p style="margin: 0 0 4px; font-size: 15px;"><strong>{{ $variant->product->name }}</strong></p>
    @if($variant->label)
        <p style="margin: 0; font-size: 13px; color: #8C7B6C;">{{ $variant->label }}</p>
    @endif
</div>

<p style="text-align: center; margin-top: 32px;">
    <a href="{{ url('/product/' . $variant->product->slug) }}" class="email-btn email-btn-forest">Shop Now</a>
</p>

<p style="font-size: 12px; color: #8C7B6C; margin-top: 24px;">
    Quantities are limited — grab yours before it sells out again.
</p>

@endsection
