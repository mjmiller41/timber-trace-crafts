@extends('layouts.app')

@section('title', 'Order Confirmed — #' . $order->id)

@section('content')

<div class="page-container py-16 md:py-24">
    <div class="max-w-2xl mx-auto">

        {{-- ============================================================ --}}
        {{-- SUCCESS MARK --}}
        {{-- ============================================================ --}}
        <div class="text-center mb-12">
            <div class="w-20 h-20 bg-forest-green flex items-center justify-center mx-auto mb-8">
                <span class="text-white text-4xl font-light" aria-hidden="true">✓</span>
            </div>
            <p class="section-label mb-4">Order #{{ $order->id }}</p>
            <h1 class="font-heading text-4xl md:text-5xl font-light text-charcoal mb-4">Order Confirmed!</h1>
            <p class="font-body text-base text-walnut leading-relaxed">
                Thank you, {{ $order->shipping_first_name }}. Your order has been placed and we're getting to work.
            </p>
        </div>

        {{-- ============================================================ --}}
        {{-- ORDER SUMMARY --}}
        {{-- ============================================================ --}}
        <div class="border border-walnut/20 bg-surface mb-8">
            <div class="px-6 py-4 border-b border-walnut/20">
                <p class="section-label">Order Summary</p>
            </div>
            <div class="px-6 py-4 space-y-4">
                @foreach($order->items as $item)
                    <div class="flex items-start gap-4 py-2">
                        <div class="w-14 h-14 bg-walnut/10 flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-body text-sm font-600 text-charcoal">{{ $item->name_snapshot }}</p>
                            @if($item->variant_label_snapshot)
                                <p class="section-label">{{ $item->variant_label_snapshot }}</p>
                            @endif
                            @if($item->personalization_text)
                                <p class="font-body text-xs text-walnut italic mt-0.5">"{{ $item->personalization_text }}"</p>
                            @endif
                            <p class="section-label">Qty: {{ $item->qty }}</p>
                        </div>
                        <p class="font-body text-sm font-600 text-charcoal flex-shrink-0">
                            ${{ number_format($item->subtotal, 2) }}
                        </p>
                    </div>
                @endforeach
            </div>
            <div class="px-6 py-4 border-t border-walnut/20 space-y-2">
                <div class="flex justify-between">
                    <span class="font-body text-sm text-walnut">Subtotal</span>
                    <span class="font-body text-sm text-charcoal">${{ number_format($order->subtotal, 2) }}</span>
                </div>
                @if($order->discount_amount > 0)
                    <div class="flex justify-between">
                        <span class="font-body text-sm text-forest-green">Discount</span>
                        <span class="font-body text-sm text-forest-green">−${{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="font-body text-sm text-walnut">Shipping</span>
                    <span class="font-body text-sm text-charcoal">
                        {{ $order->shipping_amount == 0 ? 'Free' : '$' . number_format($order->shipping_amount, 2) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="font-body text-sm text-walnut">Tax</span>
                    <span class="font-body text-sm text-charcoal">${{ number_format($order->tax_amount, 2) }}</span>
                </div>
                <div class="flex justify-between pt-3 border-t border-walnut/20 mt-3">
                    <span class="font-body text-base font-600 text-charcoal">Total</span>
                    <span class="font-body text-base font-600 text-charcoal">${{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- SHIPPING ADDRESS --}}
        {{-- ============================================================ --}}
        <div class="border border-walnut/20 bg-surface mb-8">
            <div class="px-6 py-4 border-b border-walnut/20">
                <p class="section-label">Shipping To</p>
            </div>
            <div class="px-6 py-5">
                <address class="font-body text-sm text-charcoal not-italic leading-relaxed">
                    {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
                    {{ $order->shipping_line1 }}<br>
                    @if($order->shipping_line2)
                        {{ $order->shipping_line2 }}<br>
                    @endif
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}<br>
                    United States
                </address>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- WHAT'S NEXT --}}
        {{-- ============================================================ --}}
        <div class="border border-walnut/20 bg-forest-green/5 mb-10 px-6 py-6">
            <p class="section-label mb-3">What's Next?</p>
            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <span class="w-5 h-5 bg-forest-green text-white flex items-center justify-center text-xs flex-shrink-0 mt-0.5">1</span>
                    <p class="font-body text-sm text-charcoal leading-relaxed">
                        You'll receive a confirmation email at
                        <strong>{{ $order->user?->email ?? $order->guest_email }}</strong>.
                    </p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="w-5 h-5 bg-forest-green text-white flex items-center justify-center text-xs flex-shrink-0 mt-0.5">2</span>
                    <p class="font-body text-sm text-charcoal leading-relaxed">
                        We'll carefully prepare your order in our studio — typically within 2–4 business days.
                    </p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="w-5 h-5 bg-forest-green text-white flex items-center justify-center text-xs flex-shrink-0 mt-0.5">3</span>
                    <p class="font-body text-sm text-charcoal leading-relaxed">
                        We'll notify you with a tracking number as soon as your package ships.
                    </p>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- CTA BUTTONS --}}
        {{-- ============================================================ --}}
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="{{ route('shop') }}" class="btn-forest flex-1 text-center py-4">
                Continue Shopping
            </a>
            @auth
                <a href="{{ route('account.orders') }}" class="btn-outline flex-1 text-center py-4">
                    View Your Orders
                </a>
            @else
                <a href="{{ route('order.status') }}?order={{ $order->id }}&email={{ urlencode($order->guest_email ?? '') }}"
                   class="btn-outline flex-1 text-center py-4">
                    Track Your Order
                </a>
            @endauth
        </div>

    </div>
</div>

@endsection
