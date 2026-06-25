@extends('layouts.app')

@section('title', 'Your Cart')
@section('robots', 'noindex, follow')

@section('content')

{{-- ============================================================ --}}
{{-- BREADCRUMB --}}
{{-- ============================================================ --}}
<div class="border-b border-walnut/20 py-5">
    <div class="page-container">
        <nav class="section-label" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-forest-green transition-colors">Home</a>
            <span class="mx-2 text-walnut/40">/</span>
            <span class="text-charcoal">Cart</span>
        </nav>
    </div>
</div>

<div class="page-container py-12 md:py-20">

    <h1 class="font-heading text-4xl md:text-5xl font-light text-charcoal mb-12">Your Cart</h1>

    @if(empty($cart))
        {{-- ── Empty State ── --}}
        <div class="py-24 text-center max-w-sm mx-auto">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="0.75" stroke="currentColor" class="w-16 h-16 text-walnut/30 mx-auto mb-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
            </svg>
            <p class="font-heading text-2xl font-light text-charcoal mb-3">Your cart is empty.</p>
            <p class="font-body text-sm text-walnut mb-8">Looks like you haven't added anything yet.</p>
            <a href="{{ route('shop') }}" class="btn-outline">Continue Shopping</a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 lg:gap-16">

            {{-- ====================================================== --}}
            {{-- LEFT: CART ITEMS --}}
            {{-- ====================================================== --}}
            <div class="lg:col-span-2">
                <div class="space-y-0">
                    @foreach($cart as $item)
                        <div class="flex gap-5 py-7 border-b border-walnut/20">

                            {{-- Image --}}
                            <div class="flex-shrink-0 w-20 h-20 bg-surface">
                                @if(!empty($item['image']))
                                    <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-walnut/10">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-8 h-8 text-walnut/30">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 3h18" />
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Details --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="font-heading text-lg font-light leading-snug mb-0.5">
                                            <a href="{{ url('/product/' . ($item['slug'] ?? '#')) }}"
                                               class="hover:text-forest-green transition-colors">
                                                {{ $item['name'] }}
                                            </a>
                                        </h3>
                                        @if(!empty($item['variant_label']))
                                            <p class="section-label">{{ $item['variant_label'] }}</p>
                                        @endif
                                        @if(!empty($item['personalization']))
                                            <p class="font-body text-xs text-walnut mt-1 italic">
                                                "{{ $item['personalization'] }}"
                                            </p>
                                        @endif
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="font-body text-base font-600 text-charcoal">
                                            ${{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) }}
                                        </p>
                                        @if(($item['quantity'] ?? 1) > 1)
                                            <p class="section-label">${{ number_format($item['price'] ?? 0, 2) }} each</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Qty + Remove --}}
                                <div class="flex items-center gap-4 mt-4">
                                    <form method="POST" action="{{ route('cart.update', $item['row_key']) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <label class="section-label">Qty</label>
                                        <input type="number"
                                               name="quantity"
                                               value="{{ $item['quantity'] ?? 1 }}"
                                               min="1"
                                               max="99"
                                               class="form-field w-16 text-center py-1.5 text-sm">
                                        <button type="submit" class="font-body text-xs tracking-wider uppercase text-walnut hover:text-charcoal transition-colors underline underline-offset-2">
                                            Update
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('cart.remove', $item['row_key']) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-body text-xs tracking-wider uppercase text-walnut hover:text-error transition-colors underline underline-offset-2">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    <a href="{{ route('shop') }}" class="font-body text-xs tracking-widest uppercase text-walnut hover:text-charcoal transition-colors border-b border-walnut/40 pb-0.5">
                        ← Continue Shopping
                    </a>
                </div>
            </div>

            {{-- ====================================================== --}}
            {{-- RIGHT: ORDER SUMMARY --}}
            {{-- ====================================================== --}}
            <div class="lg:col-span-1">
                <div class="border border-walnut/20 p-7 bg-surface space-y-5 sticky top-6">
                    <p class="section-label">Order Summary</p>

                    {{-- Subtotal --}}
                    <div class="flex items-center justify-between py-3 border-b border-walnut/20">
                        <span class="font-body text-sm text-charcoal">Subtotal</span>
                        <span class="font-body text-sm font-600 text-charcoal">${{ number_format($subtotal, 2) }}</span>
                    </div>

                    {{-- Coupon --}}
                    <div x-data="couponForm()" class="py-2">
                        @if(session('coupon'))
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <p class="section-label mb-0.5">Coupon Applied</p>
                                    <p class="font-body text-sm font-600 text-forest-green">{{ session('coupon') }}</p>
                                </div>
                                <form method="POST" action="{{ route('cart.coupon.remove') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-body text-xs text-walnut hover:text-error transition-colors underline">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        @else
                            <button @click="open = !open"
                                    class="font-body text-xs tracking-wider uppercase text-walnut hover:text-charcoal transition-colors underline underline-offset-2">
                                Have a coupon?
                            </button>
                            <div x-show="open || $errors->has('code')" x-transition class="mt-3">
                                <form method="POST" action="{{ route('cart.coupon') }}" class="flex gap-0">
                                    @csrf
                                    <input type="text"
                                           name="code"
                                           value="{{ old('code') }}"
                                           placeholder="Coupon code"
                                           class="form-field flex-1 py-2 text-sm @error('code') border-error @enderror">
                                    <button type="submit" class="btn-primary px-4 py-2 text-xs">Apply</button>
                                </form>
                                @error('code')
                                    <p class="font-body text-xs text-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    </div>

                    {{-- Shipping + Tax --}}
                    <div class="space-y-2 py-3 border-t border-walnut/20">
                        <div class="flex items-center justify-between">
                            <span class="font-body text-sm text-walnut">Shipping</span>
                            <span class="section-label">Calculated at checkout</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-body text-sm text-walnut">Tax</span>
                            <span class="section-label">Calculated at checkout</span>
                        </div>
                    </div>

                    {{-- Gift message --}}
                    <div class="py-3 border-t border-walnut/20">
                        <label class="section-label block mb-2">Gift Message <span class="text-walnut/50 font-400 normal-case tracking-normal">(optional)</span></label>
                        <textarea name="gift_message"
                                  rows="3"
                                  placeholder="Add a personal message for the recipient..."
                                  class="form-field text-sm resize-none"></textarea>
                    </div>

                    {{-- CTA --}}
                    <a href="{{ route('checkout.index') }}" class="btn-forest block w-full text-center py-4">
                        Proceed to Checkout
                    </a>

                    <p class="font-body text-xs text-walnut text-center">
                        Secure checkout. Free returns within 30 days.
                    </p>
                </div>
            </div>

        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    function couponForm() {
        return {
            open: false
        };
    }
</script>
@endpush
