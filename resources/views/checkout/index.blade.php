@extends('layouts.app')

@section('title', 'Checkout')
@section('robots', 'noindex, follow')

@section('content')

{{-- ============================================================ --}}
{{-- CHECKOUT HEADER --}}
{{-- ============================================================ --}}
<div class="border-b border-walnut/20 py-5">
    <div class="page-container">
        <div class="flex items-center justify-between">
            <a href="{{ route('home') }}" class="font-heading text-xl text-charcoal">Timber Trace Crafts</a>
            <nav class="section-label" aria-label="Breadcrumb">
                <a href="{{ route('cart.index') }}" class="hover:text-forest-green transition-colors">Cart</a>
                <span class="mx-2 text-walnut/40">/</span>
                <span class="text-charcoal">Checkout</span>
            </nav>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- MAIN CHECKOUT FORM --}}
{{-- ============================================================ --}}
<div class="page-container py-12 md:py-16"
     x-data="checkoutStepper()">

    {{-- Step indicators --}}
    <div class="flex items-center gap-0 mb-12 max-w-sm">
        <button @click="step = 1"
                class="flex items-center gap-2 font-body text-xs tracking-widest uppercase transition-colors"
                :class="step >= 1 ? 'text-charcoal' : 'text-walnut/40'">
            <span class="w-6 h-6 border flex items-center justify-center text-xs font-600 flex-shrink-0"
                  :class="step >= 1 ? 'border-charcoal bg-charcoal text-oak-sand' : 'border-walnut/30 text-walnut/40'">
                1
            </span>
            <span class="hidden sm:inline">Info</span>
        </button>
        <div class="flex-1 h-px bg-walnut/20 mx-3"></div>
        <button @click="step >= 2 && (step = 2)"
                class="flex items-center gap-2 font-body text-xs tracking-widest uppercase transition-colors"
                :class="step >= 2 ? 'text-charcoal' : 'text-walnut/40'">
            <span class="w-6 h-6 border flex items-center justify-center text-xs font-600 flex-shrink-0"
                  :class="step >= 2 ? 'border-charcoal bg-charcoal text-oak-sand' : 'border-walnut/30 text-walnut/40'">
                2
            </span>
            <span class="hidden sm:inline">Shipping</span>
        </button>
        <div class="flex-1 h-px bg-walnut/20 mx-3"></div>
        <button @click="step >= 3 && (step = 3)"
                class="flex items-center gap-2 font-body text-xs tracking-widest uppercase transition-colors"
                :class="step >= 3 ? 'text-charcoal' : 'text-walnut/40'">
            <span class="w-6 h-6 border flex items-center justify-center text-xs font-600 flex-shrink-0"
                  :class="step >= 3 ? 'border-charcoal bg-charcoal text-oak-sand' : 'border-walnut/30 text-walnut/40'">
                3
            </span>
            <span class="hidden sm:inline">Payment</span>
        </button>
    </div>

    <form method="POST" action="{{ route('checkout.process') }}" id="checkout-form">
        @csrf
        @include('components.honeypot')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 lg:gap-16">

            {{-- ====================================================== --}}
            {{-- FORM STEPS --}}
            {{-- ====================================================== --}}
            <div class="lg:col-span-2 space-y-0">

                {{-- ────────────────────────────────────────── --}}
                {{-- STEP 1: Contact & Shipping Address --}}
                {{-- ────────────────────────────────────────── --}}
                <div x-show="step === 1" x-cloak x-transition>
                    <h2 class="font-heading text-2xl font-light text-charcoal mb-8">Contact & Shipping</h2>

                    <div class="space-y-5">
                        {{-- Guest email (only if not logged in) --}}
                        @guest
                            <div>
                                <label for="guest_email" class="section-label block mb-2">Email Address</label>
                                <input type="email"
                                       id="guest_email"
                                       name="guest_email"
                                       value="{{ old('guest_email') }}"
                                       placeholder="you@example.com"
                                       required
                                       class="form-field {{ $errors->has('guest_email') ? 'border-error' : '' }}">
                                @error('guest_email')
                                    <p class="font-body text-xs text-error mt-1">{{ $message }}</p>
                                @enderror
                                <p class="font-body text-xs text-walnut mt-1">
                                    Already have an account?
                                    <a href="{{ route('login') }}" class="underline hover:text-charcoal">Sign in</a>
                                </p>
                            </div>
                        @endguest

                        {{-- Name row --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="shipping_first_name" class="section-label block mb-2">First Name</label>
                                <input type="text"
                                       id="shipping_first_name"
                                       name="shipping_first_name"
                                       value="{{ old('shipping_first_name', auth()->user()?->first_name) }}"
                                       required
                                       class="form-field {{ $errors->has('shipping_first_name') ? 'border-error' : '' }}">
                                @error('shipping_first_name')
                                    <p class="font-body text-xs text-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="shipping_last_name" class="section-label block mb-2">Last Name</label>
                                <input type="text"
                                       id="shipping_last_name"
                                       name="shipping_last_name"
                                       value="{{ old('shipping_last_name', auth()->user()?->last_name) }}"
                                       required
                                       class="form-field {{ $errors->has('shipping_last_name') ? 'border-error' : '' }}">
                                @error('shipping_last_name')
                                    <p class="font-body text-xs text-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Phone --}}
                        <div>
                            <label for="phone" class="section-label block mb-2">Phone <span class="text-walnut/50 font-400 normal-case tracking-normal">(optional)</span></label>
                            <input type="tel"
                                   id="phone"
                                   name="phone"
                                   value="{{ old('phone', auth()->user()?->phone) }}"
                                   placeholder="(555) 555-5555"
                                   class="form-field">
                        </div>

                        {{-- Address Line 1 --}}
                        <div>
                            <label for="shipping_address_1" class="section-label block mb-2">Address</label>
                            <input type="text"
                                   id="shipping_address_1"
                                   name="shipping_address_1"
                                   value="{{ old('shipping_address_1') }}"
                                   placeholder="123 Main Street"
                                   required
                                   class="form-field {{ $errors->has('shipping_address_1') ? 'border-error' : '' }}">
                            @error('shipping_address_1')
                                <p class="font-body text-xs text-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Address Line 2 --}}
                        <div>
                            <label for="shipping_address_2" class="section-label block mb-2">Apartment, Suite, etc. <span class="text-walnut/50 font-400 normal-case tracking-normal">(optional)</span></label>
                            <input type="text"
                                   id="shipping_address_2"
                                   name="shipping_address_2"
                                   value="{{ old('shipping_address_2') }}"
                                   placeholder="Apt 4B"
                                   class="form-field">
                        </div>

                        {{-- City / State / Zip --}}
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-3 sm:col-span-1">
                                <label for="shipping_city" class="section-label block mb-2">City</label>
                                <input type="text"
                                       id="shipping_city"
                                       name="shipping_city"
                                       value="{{ old('shipping_city') }}"
                                       required
                                       class="form-field @error('shipping_city') border-error @enderror">
                            </div>
                            <div class="col-span-3 sm:col-span-1">
                                <label for="shipping_state" class="section-label block mb-2">State</label>
                                <select id="shipping_state"
                                        name="shipping_state"
                                        required
                                        class="form-field @error('shipping_state') border-error @enderror">
                                    <option value="">— Select —</option>
                                    @foreach(['AL','AK','AZ','AR','CA','CO','CT','DE','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY'] as $state)
                                        <option value="{{ $state }}" {{ old('shipping_state') === $state ? 'selected' : '' }}>{{ $state }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-3 sm:col-span-1">
                                <label for="shipping_zip" class="section-label block mb-2">ZIP Code</label>
                                <input type="text"
                                       id="shipping_zip"
                                       name="shipping_zip"
                                       value="{{ old('shipping_zip') }}"
                                       required
                                       maxlength="10"
                                       class="form-field @error('shipping_zip') border-error @enderror">
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <button type="button" @click="step = 2" class="btn-forest px-10 py-4">
                            Continue to Shipping
                        </button>
                    </div>
                </div>

                {{-- ────────────────────────────────────────── --}}
                {{-- STEP 2: Shipping Method --}}
                {{-- ────────────────────────────────────────── --}}
                <div x-show="step === 2" x-cloak x-transition>
                    <h2 class="font-heading text-2xl font-light text-charcoal mb-8">Shipping Method</h2>

                    <div class="space-y-3">
                        @foreach($shippingMethods as $index => $method)
                            <label class="flex items-center gap-4 border border-walnut/20 p-5 cursor-pointer hover:border-charcoal transition-colors has-[:checked]:border-forest-green has-[:checked]:bg-forest-green/5">
                                <input type="radio"
                                       name="shipping_method_id"
                                       value="{{ $method['id'] ?? $index }}"
                                       {{ $index === 0 ? 'checked' : '' }}
                                       class="w-4 h-4 accent-forest-green flex-shrink-0">
                                <div class="flex-1">
                                    <p class="font-body text-sm font-600 text-charcoal">{{ $method['name'] ?? 'Standard Shipping' }}</p>
                                    @if(!empty($method['description']))
                                        <p class="font-body text-xs text-walnut mt-0.5">{{ $method['description'] }}</p>
                                    @endif
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    @if(empty($method['price']) || $method['price'] == 0)
                                        <p class="font-body text-sm font-600 text-forest-green">Free</p>
                                    @else
                                        <p class="font-body text-sm font-600 text-charcoal">+${{ number_format($method['price'], 2) }}</p>
                                    @endif
                                    @if(!empty($method['estimated_days']))
                                        <p class="section-label">{{ $method['estimated_days'] }}</p>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="flex gap-4 mt-8">
                        <button type="button" @click="step = 1"
                                class="btn-outline px-8 py-4">
                            ← Back
                        </button>
                        <button type="button" @click="step = 3" class="btn-forest px-10 py-4">
                            Continue to Payment
                        </button>
                    </div>
                </div>

                {{-- ────────────────────────────────────────── --}}
                {{-- STEP 3: Payment & Review --}}
                {{-- ────────────────────────────────────────── --}}
                <div x-show="step === 3" x-cloak x-transition>
                    <h2 class="font-heading text-2xl font-light text-charcoal mb-8">Payment</h2>

                    {{-- Order Review --}}
                    <div class="border border-walnut/20 p-6 mb-8 bg-surface">
                        <p class="section-label mb-4">Order Summary</p>
                        @foreach($cart as $item)
                            <div class="flex items-start justify-between py-3 border-b border-walnut/10 last:border-0">
                                <div class="flex-1 min-w-0">
                                    <p class="font-body text-sm text-charcoal">{{ $item['name'] }}</p>
                                    @if(!empty($item['variant_label']))
                                        <p class="section-label">{{ $item['variant_label'] }}</p>
                                    @endif
                                    <p class="section-label">Qty: {{ $item['qty'] ?? 1 }}</p>
                                </div>
                                <p class="font-body text-sm font-600 text-charcoal flex-shrink-0 ml-4">
                                    ${{ number_format(($item['price'] ?? 0) * ($item['qty'] ?? 1), 2) }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    {{-- Gift message (optional) --}}
                    <div class="mb-8">
                        <label for="gift_message" class="section-label block mb-2">Gift Message <span class="text-walnut/50 font-400 normal-case tracking-normal">(optional)</span></label>
                        <textarea id="gift_message"
                                  name="gift_message"
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Add a personal message for the recipient — we'll include it on the packing slip."
                                  class="form-field text-sm resize-none @error('gift_message') border-error @enderror">{{ old('gift_message') }}</textarea>
                        @error('gift_message')
                            <p class="mt-1 font-body text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Stripe Card Element --}}
                    <div class="mb-8">
                        <p class="section-label block mb-4">Card Details</p>
                        <div id="card-element" class="bg-white border border-charcoal p-4">
                            {{-- Stripe Elements mounts here --}}
                        </div>
                        <div id="card-errors" role="alert" class="mt-3 font-body text-sm text-error"></div>
                    </div>

                    <input type="hidden" name="payment_intent_id" id="payment-intent-id">
                    <input type="hidden" name="g-recaptcha-response" id="recaptcha-token">

                    {{-- Terms --}}
                    <p class="font-body text-xs text-walnut mb-8 leading-relaxed">
                        By placing your order you agree to our
                        <a href="{{ route('page.show', 'terms') }}" class="underline hover:text-charcoal transition-colors">Terms & Conditions</a>
                        and
                        <a href="{{ route('page.show', 'privacy') }}" class="underline hover:text-charcoal transition-colors">Privacy Policy</a>.
                    </p>

                    <div class="flex gap-4">
                        <button type="button" @click="step = 2" class="btn-outline px-8 py-4">
                            ← Back
                        </button>
                        <button type="submit" id="card-button" class="btn-forest px-10 py-4 flex-1">
                            Place Order
                        </button>
                    </div>
                </div>

            </div>

            {{-- ====================================================== --}}
            {{-- ORDER SUMMARY SIDEBAR --}}
            {{-- ====================================================== --}}
            <div class="lg:col-span-1">
                <div class="border border-walnut/20 p-6 bg-surface sticky top-6">
                    <p class="section-label mb-5">Your Order</p>

                    <div class="space-y-3 mb-5">
                        @foreach($cart as $item)
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 flex-shrink-0 bg-walnut/10 relative">
                                    @if(!empty($item['image_url']))
                                        <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                    @endif
                                    <span class="absolute -top-1.5 -right-1.5 bg-charcoal text-oak-sand w-4 h-4 flex items-center justify-center font-body text-xs font-600">
                                        {{ $item['qty'] ?? 1 }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-body text-xs text-charcoal leading-snug truncate">{{ $item['name'] }}</p>
                                    @if(!empty($item['variant_label']))
                                        <p class="section-label truncate">{{ $item['variant_label'] }}</p>
                                    @endif
                                </div>
                                <p class="font-body text-xs font-600 text-charcoal flex-shrink-0">
                                    ${{ number_format(($item['price'] ?? 0) * ($item['qty'] ?? 1), 2) }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-2 pt-4 border-t border-walnut/20 mt-2">
                        <div class="flex justify-between">
                            <span class="font-body text-sm text-walnut">Subtotal</span>
                            <span class="font-body text-sm text-charcoal font-600">${{ number_format($subtotal, 2) }}</span>
                        </div>
                        @if($discountAmount > 0)
                            <div class="flex justify-between">
                                <span class="font-body text-sm text-forest-green">Coupon ({{ session('coupon') }})</span>
                                <span class="font-body text-sm text-forest-green font-600">−${{ number_format($discountAmount, 2) }}</span>
                            </div>
                        @endif
                        @if($giftCard)
                            <div class="flex justify-between">
                                <span class="font-body text-sm text-forest-green">Gift Card ({{ $giftCard->code }})</span>
                                <span class="font-body text-sm text-forest-green font-600">up to −${{ number_format($giftCard->balance, 2) }}</span>
                            </div>
                            <p class="font-body text-xs text-walnut">Applied as store credit against your final total. Any card details below are only charged for the remaining balance.</p>
                        @endif
                        <div class="flex justify-between">
                            <span class="font-body text-sm text-walnut">Shipping</span>
                            <span class="section-label">TBD</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-body text-sm text-walnut">Tax</span>
                            <span class="section-label">TBD</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

@endsection

@push('head')
<script src="https://js.stripe.com/v3/"></script>
@endpush

@push('scripts')
<script>
    function checkoutStepper() {
        return { step: 1 };
    }

    const stripeKey = '{{ config('services.stripe.key') }}';

    document.addEventListener('DOMContentLoaded', () => {
        if (!stripeKey || !window.Stripe) return;

        const stripe  = Stripe(stripeKey);
        const elements = stripe.elements();
        const card = elements.create('card', {
            style: {
                base: {
                    fontFamily: "'Inter Tight', system-ui, -apple-system, sans-serif",
                    fontSize: '15px',
                    color: '#333333',
                    '::placeholder': { color: '#a1a1aa' },
                },
                invalid: { color: '#ba1a1a' },
            },
        });
        card.mount('#card-element');

        card.on('change', ({ error }) => {
            document.getElementById('card-errors').textContent = error ? error.message : '';
        });

        const form   = document.getElementById('checkout-form');
        const button = document.getElementById('card-button');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            button.disabled = true;
            button.textContent = 'Processing…';

            const shippingMethodId = document.querySelector('[name=shipping_method_id]:checked')?.value;
            const shippingState    = document.getElementById('shipping_state')?.value ?? '';
            const csrfToken        = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            if (window.recaptchaToken) {
                document.getElementById('recaptcha-token').value = await window.recaptchaToken('checkout');
            }

            // Step 1: create PaymentIntent server-side
            let clientSecret;
            try {
                const res = await fetch('{{ route('checkout.payment-intent') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ shipping_method_id: shippingMethodId, shipping_state: shippingState }),
                });
                const data = await res.json();
                if (data.error) {
                    showError(data.error);
                    resetButton();
                    return;
                }
                // Gift card covers the whole order — no card charge required.
                // Submit directly; the server creates the order with no PaymentIntent.
                if (data.fully_covered) {
                    form.submit();
                    return;
                }
                clientSecret = data.client_secret;
            } catch {
                showError('Could not connect to payment service. Please try again.');
                resetButton();
                return;
            }

            // Step 2: confirm card payment with Stripe
            const { paymentIntent, error } = await stripe.confirmCardPayment(clientSecret, {
                payment_method: { card },
            });

            if (error) {
                showError(error.message);
                resetButton();
                return;
            }

            // Step 3: attach intent ID and submit
            document.getElementById('payment-intent-id').value = paymentIntent.id;
            form.submit();
        });

        function showError(msg) {
            document.getElementById('card-errors').textContent = msg;
        }

        function resetButton() {
            button.disabled = false;
            button.textContent = 'Place Order';
        }
    });
</script>
@endpush
