<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        $cart = CartService::getCart();
        $subtotal = CartService::subtotal($cart);

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
            'price' => (float) $product->currentPrice(),
            'qty' => (int) $validated['qty'],
            'image_url' => $product->primary_image_url ?? null,
        ];

        CartService::add($item);

        return redirect()->back()->with('success', 'Item added to cart.');
    }

    public function update(Request $request, string $rowKey): RedirectResponse
    {
        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        CartService::update($rowKey, (int) $validated['qty']);

        return redirect()->back()->with('success', 'Cart updated.');
    }

    public function remove(string $rowKey): RedirectResponse
    {
        CartService::remove($rowKey);

        return redirect()->back()->with('success', 'Item removed from cart.');
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        session(['coupon' => strtoupper($validated['code'])]);

        return redirect()->back()->with('success', 'Coupon applied.');
    }

    public function removeCoupon(): RedirectResponse
    {
        session()->forget('coupon');

        return redirect()->back()->with('success', 'Coupon removed.');
    }
}
