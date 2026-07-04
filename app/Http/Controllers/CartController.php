<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Support\Analytics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index(): View
    {
        $cart = $this->cartService->getCart();
        $subtotal = $this->cartService->subtotal($cart);

        return view('cart.index', compact('cart', 'subtotal'));
    }

    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'qty' => ['required', 'integer', 'min:1', 'max:99'],
            'personalization_text' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $variant = ProductVariant::findOrFail($validated['variant_id']);

        if ($variant->product_id !== $product->id) {
            return redirect()->back()->withErrors(['variant_id' => 'This option does not belong to that product.']);
        }

        if ($product->status !== 'active' || ! $variant->is_enabled) {
            return redirect()->back()->withErrors(['product_id' => 'This item is not currently available.']);
        }

        $personalizationText = $validated['personalization_text'] ?? null;
        $personalizationPrice = ($product->personalization_type === 'addon' && $personalizationText)
            ? (float) ($product->personalization_price ?? 0)
            : 0.0;

        $rowKey = md5($validated['product_id'].'-'.$validated['variant_id'].'-'.$personalizationText);

        $item = [
            'row_key' => $rowKey,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'sku' => $variant->sku ?? '',
            'name' => $product->name,
            'variant_label' => $variant->label ?? '',
            'personalization_text' => $personalizationText,
            'personalization_price' => $personalizationPrice,
            // A variant price is an absolute per-variant override; fall back to
            // the product's current (sale-aware) price when it isn't set.
            'price' => (float) ($variant->price ?? $product->currentPrice()),
            'qty' => (int) $validated['qty'],
            'image_url' => $product->primary_image_url ?? null,
        ];

        $this->cartService->add($item);

        Analytics::record('add_to_cart', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'qty' => (int) $validated['qty'],
            'value' => round($item['price'] * (int) $validated['qty'], 2),
        ]);

        return redirect()->back()->with('success', 'Item added to cart.');
    }

    public function update(Request $request, string $rowKey): RedirectResponse
    {
        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $this->cartService->update($rowKey, (int) $validated['qty']);

        return redirect()->back()->with('success', 'Cart updated.');
    }

    public function remove(string $rowKey): RedirectResponse
    {
        $this->cartService->remove($rowKey);

        return redirect()->back()->with('success', 'Item removed from cart.');
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $code = strtoupper($validated['code']);
        $subtotal = $this->cartService->subtotal($this->cartService->getCart());

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->isValid($subtotal)) {
            return redirect()->back()->withErrors(['code' => 'This coupon code is invalid or has expired.']);
        }

        session(['coupon' => $code]);

        return redirect()->back()->with('success', 'Coupon applied.');
    }

    public function removeCoupon(): RedirectResponse
    {
        session()->forget('coupon');

        return redirect()->back()->with('success', 'Coupon removed.');
    }
}
