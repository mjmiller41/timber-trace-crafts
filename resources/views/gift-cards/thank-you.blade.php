@extends('layouts.app')

@section('title', 'Gift Card Purchased')
@section('robots', 'noindex, follow')

@section('content')

<div class="page-container py-20 md:py-28">
    <div class="max-w-xl mx-auto text-center">
        <p class="section-label mb-4">Thank You</p>
        <h1 class="font-heading text-4xl md:text-5xl font-light text-charcoal mb-6">Your gift card is on its way</h1>
        <p class="font-body text-sm text-walnut leading-relaxed mb-4">
            Payment received — thank you! We're emailing the gift card to your recipient with a
            code they can redeem at checkout. If you chose a future send date, it will arrive
            on that day.
        </p>
        <p class="font-body text-sm text-walnut leading-relaxed mb-10">
            A receipt is on its way to your inbox too. It can take a minute or two to arrive.
        </p>
        <a href="{{ route('shop') }}" class="btn-primary">Continue Shopping</a>
    </div>
</div>

@endsection
