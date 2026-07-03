<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Wishlist;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(): View
    {
        return view('account.dashboard');
    }

    public function orders(): View
    {
        $orders = auth()->user()
            ->orders()
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('account.orders.index', compact('orders'));
    }

    public function orderShow(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return view('account.orders.show', compact('order'));
    }

    /**
     * Re-add the still-available lines from a past order to the cart at current
     * price/availability. Lines whose product or variant is gone, inactive, or
     * disabled are skipped and reported back to the customer.
     */
    public function orderReorder(Order $order, CartService $cart): RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $added = 0;
        $skipped = [];

        foreach ($order->items as $line) {
            $product = $line->product_id ? Product::find($line->product_id) : null;
            $variant = $line->variant_id ? ProductVariant::find($line->variant_id) : null;

            $label = $line->name_snapshot ?? $product?->name ?? 'An item';

            if (! $product || ! $variant || $variant->product_id !== $product->id) {
                $skipped[] = $label;

                continue;
            }

            if ($product->status !== 'active' || ! $variant->is_enabled) {
                $skipped[] = $label;

                continue;
            }

            $personalizationText = $line->personalization_text ?: null;
            $personalizationPrice = ($product->personalization_type === 'addon' && $personalizationText)
                ? (float) ($product->personalization_price ?? 0)
                : 0.0;

            $cart->add([
                'row_key' => md5($product->id.'-'.$variant->id.'-'.$personalizationText),
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'sku' => $variant->sku ?? '',
                'name' => $product->name,
                'variant_label' => $variant->label ?? '',
                'personalization_text' => $personalizationText,
                'personalization_price' => $personalizationPrice,
                // Current (sale-aware) price, mirroring the add-to-cart flow —
                // a variant price is an absolute override.
                'price' => (float) ($variant->price ?? $product->currentPrice()),
                'qty' => (int) $line->qty,
                'image_url' => $product->primary_image_url ?? null,
            ]);

            $added++;
        }

        if ($added === 0) {
            return redirect()->route('account.orders.show', $order)
                ->with('error', 'None of the items from this order are available to reorder right now.');
        }

        $message = $added === 1 ? '1 item added to your cart.' : "{$added} items added to your cart.";
        if (! empty($skipped)) {
            $message .= ' Skipped (no longer available): '.implode(', ', $skipped).'.';
        }

        return redirect()->route('cart.index')->with('success', $message);
    }

    public function orderInvoice(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load(['items', 'statusHistory']);

        return view('account.orders.invoice', compact('order'));
    }

    public function wishlist(): View
    {
        $items = Wishlist::with('variant.product.media.media')
            ->where('user_id', auth()->id())
            ->get();

        return view('account.wishlist', compact('items'));
    }

    public function wishlistAdd(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
        ]);

        Wishlist::firstOrCreate([
            'user_id' => auth()->id(),
            'product_variant_id' => $validated['variant_id'],
        ]);

        return redirect()->back()->with('success', 'Added to wishlist.');
    }

    public function wishlistRemove(ProductVariant $variant): RedirectResponse
    {
        Wishlist::where('user_id', auth()->id())
            ->where('product_variant_id', $variant->id)
            ->delete();

        return redirect()->back()->with('success', 'Removed from wishlist.');
    }

    public function addresses(): View
    {
        $addresses = Address::where('user_id', auth()->id())->get();

        return view('account.addresses', compact('addresses'));
    }

    public function addressStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'zip' => ['required', 'string', 'max:10'],
            'country' => ['required', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
        ]);

        if (! empty($validated['is_default'])) {
            Address::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        Address::create(array_merge($validated, ['user_id' => auth()->id()]));

        return redirect()->route('account.addresses')->with('success', 'Address saved.');
    }

    public function addressUpdate(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'zip' => ['required', 'string', 'max:10'],
            'country' => ['required', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
        ]);

        if (! empty($validated['is_default'])) {
            Address::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $address->update($validated);

        return redirect()->route('account.addresses')->with('success', 'Address updated.');
    }

    public function addressDelete(Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $address->delete();

        return redirect()->route('account.addresses')->with('success', 'Address deleted.');
    }

    public function profile(): View
    {
        return view('account.profile');
    }

    public function profileUpdate(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $emailChanged = $user->email !== $validated['email'];

        // A hijacked session could otherwise swap the account email, then use
        // the (attacker-controlled) new address to reset the password.
        if (! empty($validated['password']) || $emailChanged) {
            if (empty($validated['current_password']) || ! Hash::check($validated['current_password'], $user->password)) {
                return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->save();
            $user->sendEmailVerificationNotification();
        }

        return redirect()->route('account.profile')->with('success', 'Profile updated.');
    }
}
