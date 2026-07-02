<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Wishlist;
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
