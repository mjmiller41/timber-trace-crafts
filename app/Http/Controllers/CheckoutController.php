<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmationMail;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Services\CartService;
use App\Services\RecaptchaService;
use App\Services\ShippingService;
use App\Services\StripeService;
use App\Services\TaxService;
use App\Support\Analytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ShippingService $shippingService,
        private readonly TaxService $taxService,
        private readonly StripeService $stripeService,
        private readonly RecaptchaService $recaptchaService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $cart = $this->cartService->getCart();

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $subtotal = $this->cartService->subtotal($cart);
        $shippingMethods = $this->shippingService->getAvailableMethods();
        $coupon = $this->resolveValidCoupon($subtotal);
        $discountAmount = $coupon ? $coupon->calculateDiscount($cart) : 0.0;

        Analytics::record('begin_checkout', [
            'items' => count($cart),
            'value' => round($subtotal, 2),
        ]);

        return view('checkout.index', compact('cart', 'subtotal', 'shippingMethods', 'coupon', 'discountAmount'));
    }

    /**
     * Create a Stripe PaymentIntent and return the client secret (called via AJAX).
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_method_id' => ['required', 'integer', 'exists:shipping_methods,id'],
            'shipping_state' => ['nullable', 'string', 'max:2'],
        ]);

        $cart = $this->cartService->getCart();
        if (empty($cart)) {
            return response()->json(['error' => 'Your cart is empty.'], 422);
        }

        $cart = $this->revalidateCartPrices($cart);

        $shippingMethod = ShippingMethod::where('id', $validated['shipping_method_id'])->where('active', true)->first();
        $methods = $this->shippingService->getAvailableMethods();
        $methodData = $shippingMethod ? collect($methods)->firstWhere('id', $shippingMethod->id) : null;

        if (! $methodData) {
            return response()->json(['error' => 'That shipping method is no longer available.'], 422);
        }

        $shippingAmount = (float) $methodData['price'];

        $subtotal = $this->cartService->subtotal($cart);
        $discountAmount = $this->resolveCouponDiscount($cart, $subtotal);
        $taxAmount = $this->taxService->calculate($subtotal - $discountAmount + $shippingAmount, $validated['shipping_state'] ?? '');
        $total = round($subtotal - $discountAmount + $shippingAmount + $taxAmount, 2);
        $totalCents = (int) round($total * 100);

        $buyerEmail = auth()->user()?->email;

        try {
            $intent = $this->stripeService->createPaymentIntent($totalCents, $buyerEmail);

            return response()->json(['client_secret' => $intent->client_secret]);
        } catch (\Throwable $e) {
            Log::error('Stripe PaymentIntent creation failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Could not initialise payment. Please try again.'], 500);
        }
    }

    public function process(Request $request): View|RedirectResponse
    {
        if (! $this->recaptchaService->verify($request->input('g-recaptcha-response'), 'checkout', $request->ip())) {
            return back()->withErrors([
                'payment' => 'We could not verify you\'re human. Please try again.',
            ])->withInput();
        }

        $validated = $request->validate([
            'guest_email' => [Rule::requiredIf(! auth()->check()), 'nullable', 'email', 'max:255'],
            'shipping_first_name' => ['required', 'string', 'max:100'],
            'shipping_last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'shipping_address_1' => ['required', 'string', 'max:255'],
            'shipping_address_2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'shipping_state' => ['required', 'string', 'max:2'],
            'shipping_zip' => ['required', 'string', 'max:10'],
            'shipping_method_id' => ['required', 'integer', 'exists:shipping_methods,id'],
            'payment_intent_id' => ['required', 'string', 'max:255'],
            'gift_message' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = $this->cartService->getCart();
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $cart = $this->revalidateCartPrices($cart);

        // Verify the Stripe PaymentIntent succeeded
        try {
            $intent = $this->stripeService->verifyPaymentIntent($validated['payment_intent_id']);
        } catch (\Throwable $e) {
            Log::error('Stripe payment verification failed', ['error' => $e->getMessage()]);

            return back()->withErrors([
                'payment' => 'Payment could not be verified. Please try again or contact support.',
            ])->withInput();
        }

        // Resolve shipping
        $shippingMethod = ShippingMethod::where('id', $validated['shipping_method_id'])->where('active', true)->first();
        $methods = $this->shippingService->getAvailableMethods();
        $methodData = $shippingMethod ? collect($methods)->firstWhere('id', $shippingMethod->id) : null;

        if (! $methodData) {
            return back()->withErrors(['shipping_method_id' => 'That shipping method is no longer available.'])->withInput();
        }

        $shippingAmount = (float) $methodData['price'];

        // Recalculate totals server-side (never trust client)
        $subtotal = $this->cartService->subtotal($cart);
        $coupon = $this->resolveValidCoupon($subtotal);
        $discountAmount = $coupon ? $coupon->calculateDiscount($cart) : 0.0;
        $taxAmount = $this->taxService->calculate($subtotal - $discountAmount + $shippingAmount, $validated['shipping_state']);
        $total = round($subtotal - $discountAmount + $shippingAmount + $taxAmount, 2);

        $buyerEmail = auth()->check()
            ? auth()->user()->email
            : $validated['guest_email'];

        // C1: Reject if Stripe amount doesn't match our server-calculated total
        $expectedCents = (int) round($total * 100);
        if ($intent->amount_received !== $expectedCents || $intent->currency !== 'usd') {
            Log::warning('Checkout amount mismatch', [
                'expected_cents' => $expectedCents,
                'received_cents' => $intent->amount_received,
                'currency' => $intent->currency,
            ]);

            return back()->withErrors(['payment' => 'Payment amount does not match order total.'])->withInput();
        }

        // C2: Idempotency — block replay of the same payment intent
        if (Order::where('stripe_payment_intent_id', $intent->id)->exists()) {
            $existingOrder = Order::where('stripe_payment_intent_id', $intent->id)->first();

            session()->push('confirmed_orders', $existingOrder->id);

            return redirect()->route('checkout.confirmation', ['order' => $existingOrder]);
        }

        // Create order + items in a transaction (RuntimeException from C3 rolls back and surfaces as error)
        try {
            $order = DB::transaction(function () use (
                $validated, $cart, $subtotal, $discountAmount,
                $shippingAmount, $taxAmount, $total,
                $shippingMethod, $intent, $buyerEmail, $coupon
            ) {
                // C3: Pessimistic stock lock — prevent overselling under concurrent checkouts.
                // Lock in a consistent (variant_id) order across all requests so two
                // checkouts sharing overlapping cart lines can't deadlock each other.
                $lockOrderedCart = collect($cart)->sortBy('variant_id')->all();

                foreach ($lockOrderedCart as $item) {
                    if (! isset($item['variant_id'])) {
                        continue;
                    }

                    $variant = ProductVariant::where('id', $item['variant_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $variant || $variant->stock_qty < $item['qty']) {
                        throw new \RuntimeException("Item '{$item['name']}' is out of stock.");
                    }

                    $variant->decrement('stock_qty', $item['qty']);
                }

                $order = Order::create([
                    'user_id' => auth()->id(),
                    'guest_email' => auth()->check() ? null : $buyerEmail,
                    'status' => 'processing',
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'shipping_amount' => $shippingAmount,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                    'coupon_id' => $coupon?->id,
                    'coupon_code_snapshot' => $coupon?->code,
                    'stripe_payment_intent_id' => $intent->id,
                    'shipping_method' => $shippingMethod->name,
                    'gift_message' => $validated['gift_message'] ?? null,
                    'shipping_first_name' => $validated['shipping_first_name'],
                    'shipping_last_name' => $validated['shipping_last_name'],
                    'shipping_line1' => $validated['shipping_address_1'],
                    'shipping_line2' => $validated['shipping_address_2'] ?? null,
                    'shipping_city' => $validated['shipping_city'],
                    'shipping_state' => strtoupper($validated['shipping_state']),
                    'shipping_zip' => $validated['shipping_zip'],
                    'shipping_country' => 'US',
                    'shipping_phone' => $validated['phone'] ?? null,
                    'billing_first_name' => $validated['shipping_first_name'],
                    'billing_last_name' => $validated['shipping_last_name'],
                    'billing_line1' => $validated['shipping_address_1'],
                    'billing_line2' => $validated['shipping_address_2'] ?? null,
                    'billing_city' => $validated['shipping_city'],
                    'billing_state' => strtoupper($validated['shipping_state']),
                    'billing_zip' => $validated['shipping_zip'],
                    'billing_country' => 'US',
                    'ip_address' => request()->ip(),
                ]);

                foreach ($cart as $item) {
                    $itemSubtotal = round(($item['price'] + ($item['personalization_price'] ?? 0)) * $item['qty'], 2);
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'],
                        'sku_snapshot' => $item['sku'] ?? '',
                        'name_snapshot' => $item['name'],
                        'variant_label_snapshot' => $item['variant_label'] ?? '',
                        'personalization_text' => $item['personalization_text'] ?? null,
                        'price_snapshot' => $item['price'],
                        'qty' => $item['qty'],
                        'subtotal' => $itemSubtotal,
                    ]);
                }

                $order->statusHistory()->create([
                    'status' => 'processing',
                    'note' => 'Order placed via website.',
                ]);

                if ($coupon) {
                    // Lock the coupon row so concurrent checkouts consume it
                    // serially and used_count stays exact. The payment is already
                    // captured at the discounted total, so the order is always
                    // honoured; if a concurrent checkout exhausted the cap first
                    // we log the overshoot for reconciliation rather than
                    // rejecting a customer who has already paid.
                    $lockedCoupon = Coupon::whereKey($coupon->id)->lockForUpdate()->first();

                    if ($lockedCoupon) {
                        if ($lockedCoupon->max_uses !== null && $lockedCoupon->used_count >= $lockedCoupon->max_uses) {
                            Log::warning('Coupon max_uses exceeded under concurrent checkout', [
                                'coupon_id' => $lockedCoupon->id,
                                'code' => $lockedCoupon->code,
                                'used_count' => $lockedCoupon->used_count,
                                'max_uses' => $lockedCoupon->max_uses,
                                'payment_intent' => $intent->id,
                            ]);
                        }

                        $lockedCoupon->increment('used_count');
                    }
                }

                return $order;
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['stock' => $e->getMessage()])->withInput();
        }

        $this->cartService->clear();

        try {
            Mail::to($buyerEmail)->queue(new OrderConfirmationMail($order->load('items')));
        } catch (\Throwable $e) {
            Log::error('Order confirmation email failed', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        session()->push('confirmed_orders', $order->id);

        return redirect()->route('checkout.confirmation', ['order' => $order]);
    }

    public function confirmation(Order $order): View
    {
        if (auth()->check()) {
            abort_unless($order->user_id === auth()->id(), 403);
        } else {
            // Guests are authorised via the session set at checkout, so the
            // email never travels in the URL (access logs / history / Referer).
            abort_unless(
                in_array($order->id, (array) session('confirmed_orders', []), true),
                403
            );
        }

        $order->load('items');

        // Record the purchase once per session per order so page refreshes on the
        // confirmation screen don't inflate the conversion count.
        $tracked = (array) session('tracked_purchases', []);
        if (! in_array($order->id, $tracked, true)) {
            Analytics::record('purchase', [
                'order_id' => $order->id,
                'value' => round((float) $order->total, 2),
                'currency' => 'USD',
                'items' => $order->items->count(),
            ]);
            session(['tracked_purchases' => [...$tracked, $order->id]]);
        }

        return view('checkout.confirmation', compact('order'));
    }

    /**
     * Reload each cart line's price from the database so a stale add-to-cart
     * snapshot (post price-change or expired sale) never reaches checkout.
     *
     * @param  array<string, array<string, mixed>>  $cart
     * @return array<string, array<string, mixed>>
     */
    private function revalidateCartPrices(array $cart): array
    {
        return collect($cart)->map(function (array $item) {
            $variant = ProductVariant::with('product')->find($item['variant_id'] ?? null);

            if ($variant && $variant->product) {
                $item['price'] = (float) ($variant->price ?? $variant->product->currentPrice());
            }

            return $item;
        })->all();
    }

    private function resolveValidCoupon(float $subtotal): ?Coupon
    {
        $code = session('coupon');
        if (! $code) {
            return null;
        }

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->isValid($subtotal)) {
            return null;
        }

        return $coupon;
    }

    private function resolveCouponDiscount(array $cart, float $subtotal): float
    {
        $coupon = $this->resolveValidCoupon($subtotal);

        return $coupon ? $coupon->calculateDiscount($cart) : 0.0;
    }
}
