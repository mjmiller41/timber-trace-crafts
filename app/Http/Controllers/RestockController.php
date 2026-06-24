<?php

namespace App\Http\Controllers;

use App\Models\RestockRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RestockController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
        ]);

        RestockRequest::firstOrCreate([
            'email' => strtolower($validated['email']),
            'product_variant_id' => $validated['variant_id'],
        ]);

        return redirect()->back()->with('success', "We'll notify you at {$validated['email']} when this item is back in stock.");
    }
}
