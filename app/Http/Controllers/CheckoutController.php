<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmationMail;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Services\CartService;
use App\Services\ShippingService;
use App\Services\StripeService;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ShippingService $shippingService,
        private readonly TaxService $taxService,
        private readonly StripeService $stripeService,
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

        $shippingMethod = ShippingMethod::findOrFail($validated['shipping_method_id']);
        $methods = $this->shippingService->getAvailableMethods();
        $methodData = collect($methods)->firstWhere('id', $shippingMethod->id);
        $shippingAmount = $methodData ? (float) $methodData['price'] : 0.0;

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
        $validated = $request->validate([
            'guest_email' => ['required_if:user,null', 'nullable', 'email', 'max:255'],
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
        $shippingMethod = ShippingMethod::findOrFail($validated['shipping_method_id']);
        $methods = $this->shippingService->getAvailableMethods();
        $methodData = collect($methods)->firstWhere('id', $shippingMethod->id);
        $shippingAmount = $methodData ? (float) $methodData['price'] : 0.0;

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

            return redirect()->route('checkout.confirmation', [
                'order' => $existingOrder,
                'email' => $buyerEmail,
            ]);
        }

        // Create order + items in a transaction (RuntimeException from C3 rolls back and surfaces as error)
        try {
            $order = DB::transaction(function () use (
                $validated, $cart, $subtotal, $discountAmount,
                $shippingAmount, $taxAmount, $total,
                $shippingMethod, $intent, $buyerEmail, $coupon
            ) {
                // C3: Pessimistic stock lock — prevent overselling under concurrent checkouts
                foreach ($cart as $item) {
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
                    $coupon->increment('used_count');
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

        return redirect()->route('checkout.confirmation', [
            'order' => $order,
            'email' => $buyerEmail,
        ]);
    }

    public function confirmation(Order $order): View
    {
        if (auth()->check()) {
            abort_unless($order->user_id === auth()->id(), 403);
        } else {
            abort_unless(
                request()->query('email') &&
                strtolower($order->guest_email ?? '') === strtolower(request()->query('email')),
                403
            );
        }

        $order->load('items');

        return view('checkout.confirmation', compact('order'));
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
