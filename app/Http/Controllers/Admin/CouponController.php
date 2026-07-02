<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $coupons = Coupon::orderByDesc('created_at')->paginate(25);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('admin.coupons.create', compact('categories', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'type' => ['required', 'string', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0', $request->input('type') === 'percent' ? 'max:100' : 'max:999999'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'active' => ['boolean'],
        ]);

        $validated['code'] = strtoupper($validated['code']);

        Coupon::create($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon): View
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('admin.coupons.edit', compact('coupon', 'categories', 'products'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', "unique:coupons,code,{$coupon->id}"],
            'type' => ['required', 'string', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0', $request->input('type') === 'percent' ? 'max:100' : 'max:999999'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'active' => ['boolean'],
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $coupon->update($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted.');
    }
}
