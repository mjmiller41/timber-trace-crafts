@extends('layouts.app')

@section('title', 'Gift Cards')
@section('meta_description', 'Give the gift of handmade woodwork. Buy a Timber Trace Crafts gift card online — choose an amount, add a personal message, and we\'ll email it to the recipient.')

@section('content')

{{-- ============================================================ --}}
{{-- PAGE HEADER --}}
{{-- ============================================================ --}}
<div class="border-b border-walnut/20 py-10 md:py-14">
    <div class="page-container">
        <p class="section-label mb-4">Give the Gift of Handmade</p>
        <h1 class="font-heading text-4xl md:text-5xl font-light text-charcoal">Gift Cards</h1>
        <p class="font-body text-sm text-walnut leading-relaxed mt-4 max-w-2xl">
            Not sure which piece they'll love? Let them choose. Pick an amount, add a personal
            note, and we'll email the gift card straight to the recipient — redeemable at
            checkout on anything in the shop. Any unused balance stays on the card.
        </p>
    </div>
</div>

<div class="page-container py-14 md:py-20">
    <div class="max-w-2xl">

        <div id="gift-card-errors" class="hidden border border-error/40 bg-error/5 px-6 py-5 mb-8">
            <p class="font-body text-sm text-error font-600"></p>
        </div>

        <form id="gift-card-form" class="space-y-8">
            @csrf

            {{-- Amount --}}
            <div>
                <label class="section-label block mb-3">Choose an Amount</label>
                <div class="grid grid-cols-3 gap-3" id="tier-grid">
                    @foreach($tiers as $tier)
                        <label class="cursor-pointer">
                            <input type="radio" name="amount_tier" value="{{ $tier }}" class="peer sr-only" @if($loop->first) checked @endif>
                            <span class="block text-center border border-walnut/30 py-3 font-body text-charcoal peer-checked:border-forest-green peer-checked:bg-forest-green/5 peer-checked:text-forest-green transition">
                                ${{ $tier }}
                            </span>
                        </label>
                    @endforeach
                    <label class="cursor-pointer">
                        <input type="radio" name="amount_tier" value="custom" class="peer sr-only">
                        <span class="block text-center border border-walnut/30 py-3 font-body text-charcoal peer-checked:border-forest-green peer-checked:bg-forest-green/5 peer-checked:text-forest-green transition">
                            Custom
                        </span>
                    </label>
                </div>
                <div id="custom-amount-wrap" class="hidden mt-4">
                    <label for="custom_amount" class="section-label block mb-2">Custom amount (${{ $min }}–${{ $max }})</label>
                    <input type="number" id="custom_amount" name="custom_amount" min="{{ $min }}" max="{{ $max }}" step="1"
                           class="form-field" placeholder="e.g. 120">
                </div>
            </div>

            {{-- Recipient --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="recipient_name" class="section-label block mb-2">Recipient Name <span class="text-walnut/60 normal-case">(optional)</span></label>
                    <input type="text" id="recipient_name" name="recipient_name" maxlength="100" class="form-field">
                </div>
                <div>
                    <label for="recipient_email" class="section-label block mb-2">Recipient Email</label>
                    <input type="email" id="recipient_email" name="recipient_email" required class="form-field">
                </div>
            </div>

            {{-- Purchaser --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="purchaser_name" class="section-label block mb-2">Your Name <span class="text-walnut/60 normal-case">(optional)</span></label>
                    <input type="text" id="purchaser_name" name="purchaser_name" maxlength="100" value="{{ auth()->user()?->name }}" class="form-field">
                </div>
                <div>
                    <label for="purchaser_email" class="section-label block mb-2">Your Email <span class="text-walnut/60 normal-case">(for your receipt)</span></label>
                    <input type="email" id="purchaser_email" name="purchaser_email" required value="{{ auth()->user()?->email }}" class="form-field">
                </div>
            </div>

            {{-- Message --}}
            <div>
                <label for="message" class="section-label block mb-2">Personal Message <span class="text-walnut/60 normal-case">(optional)</span></label>
                <textarea id="message" name="message" rows="3" maxlength="450" class="form-field" placeholder="Happy birthday! Pick something you'll love…"></textarea>
            </div>

            {{-- Send date --}}
            <div>
                <label for="send_date" class="section-label block mb-2">Send Date <span class="text-walnut/60 normal-case">(optional — leave blank to send now)</span></label>
                <input type="date" id="send_date" name="send_date" min="{{ now()->toDateString() }}" class="form-field sm:w-1/2">
            </div>

            {{-- Payment --}}
            <div>
                <label class="section-label block mb-2">Payment Details</label>
                <div id="card-element" class="form-field py-4"></div>
                <p id="card-errors" class="font-body text-xs text-error mt-2" role="alert"></p>
            </div>

            <button type="submit" id="pay-button" class="btn-primary w-full sm:w-auto">
                Buy Gift Card
            </button>
        </form>
    </div>
</div>

@endsection

@push('head')
<script src="https://js.stripe.com/v3/"></script>
@endpush

@push('scripts')
<script>
    const stripeKey = '{{ config('services.stripe.key') }}';
    const paymentIntentUrl = '{{ route('gift-cards.payment-intent') }}';
    const thankYouUrl = '{{ route('gift-cards.thank-you') }}';

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('gift-card-form');
        const button = document.getElementById('pay-button');
        const customWrap = document.getElementById('custom-amount-wrap');
        const errorBox = document.getElementById('gift-card-errors');

        // Toggle custom amount field
        document.querySelectorAll('[name=amount_tier]').forEach((el) => {
            el.addEventListener('change', () => {
                customWrap.classList.toggle('hidden', el.value !== 'custom' || !el.checked);
            });
        });

        if (!stripeKey || !window.Stripe) {
            showTopError('Payment is temporarily unavailable. Please try again later.');
            button.disabled = true;
            return;
        }

        const stripe = Stripe(stripeKey);
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

        function selectedAmount() {
            const tier = document.querySelector('[name=amount_tier]:checked')?.value;
            if (tier === 'custom') {
                return parseInt(document.getElementById('custom_amount').value || '0', 10);
            }
            return parseInt(tier || '0', 10);
        }

        function showTopError(msg) {
            errorBox.querySelector('p').textContent = msg;
            errorBox.classList.remove('hidden');
        }

        function reset() {
            button.disabled = false;
            button.textContent = 'Buy Gift Card';
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorBox.classList.add('hidden');
            button.disabled = true;
            button.textContent = 'Processing…';

            const amount = selectedAmount();
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            const payload = {
                amount,
                recipient_email: document.getElementById('recipient_email').value,
                recipient_name: document.getElementById('recipient_name').value,
                purchaser_email: document.getElementById('purchaser_email').value,
                purchaser_name: document.getElementById('purchaser_name').value,
                message: document.getElementById('message').value,
                send_date: document.getElementById('send_date').value,
            };

            // Step 1: create the PaymentIntent server-side (validates too)
            let clientSecret;
            try {
                const res = await fetch(paymentIntentUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (!res.ok || data.error) {
                    const first = data.errors ? Object.values(data.errors)[0][0] : (data.error || 'Please check your details and try again.');
                    showTopError(first);
                    reset();
                    return;
                }
                clientSecret = data.client_secret;
            } catch {
                showTopError('Could not connect to the payment service. Please try again.');
                reset();
                return;
            }

            // Step 2: confirm the card payment
            const { paymentIntent, error } = await stripe.confirmCardPayment(clientSecret, {
                payment_method: { card },
            });

            if (error) {
                document.getElementById('card-errors').textContent = error.message;
                reset();
                return;
            }

            // Step 3: payment confirmed. The card is issued + emailed off the
            // Stripe webhook — just send the buyer to the thank-you page.
            if (paymentIntent && paymentIntent.status === 'succeeded') {
                window.location.href = thankYouUrl;
            } else {
                showTopError('Your payment is still processing. You will receive a confirmation email shortly.');
                reset();
            }
        });
    });
</script>
@endpush
