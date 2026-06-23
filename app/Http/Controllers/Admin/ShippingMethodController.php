<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingMethodController extends Controller
{
    public function index(): View
    {
        $methods = ShippingMethod::orderBy('sort_order')->get();

        return view('admin.shipping.index', compact('methods'));
    }

    public function create(): View
    {
        return view('admin.shipping.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'service_code' => ['required', 'string', 'max:100', 'unique:shipping_methods,service_code'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
        ]);

        ShippingMethod::create($validated);

        return redirect()->route('admin.shipping.index')->with('success', 'Shipping method created.');
    }

    public function edit(ShippingMethod $shipping): View
    {
        return view('admin.shipping.edit', compact('shipping'));
    }

    public function update(Request $request, ShippingMethod $shipping): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'service_code' => ['required', 'string', 'max:100', "unique:shipping_methods,service_code,{$shipping->id}"],
            'description' => ['nullable', 'string', 'max:500'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
        ]);

        $shipping->update($validated);

        return redirect()->route('admin.shipping.index')->with('success', 'Shipping method updated.');
    }

    public function destroy(ShippingMethod $shipping): RedirectResponse
    {
        $shipping->delete();

        return redirect()->route('admin.shipping.index')->with('success', 'Shipping method deleted.');
    }
}
