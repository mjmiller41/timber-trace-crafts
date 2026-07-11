<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(string $slug): View|RedirectResponse
    {
        // Consolidate duplicate butterfly slugs (`...-gift`, `...-gift-2`, …) to
        // the canonical product URL with a permanent redirect. The canonical
        // slug is never a key in this map, so it resolves normally (no loop),
        // and an unmapped slug falls through to the 404-ing lookup below. (A21)
        $canonical = config('catalog.slug_redirects', [])[$slug] ?? null;
        if ($canonical !== null && $canonical !== $slug) {
            return redirect()->route('product.show', ['slug' => $canonical], 301);
        }

        $product = Product::with([
            'category',
            'variants' => fn ($q) => $q->orderBy('sort_order'),
            'media' => fn ($q) => $q->orderBy('sort_order'),
            'media.media',
            'tags',
            'reviews' => fn ($q) => $q->where('status', 'approved')->latest()->limit(10),
            'reviews.user',
        ])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $relatedProducts = Product::with(['media.media'])
            ->where('status', 'active')
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->limit(4)
            ->get();

        return view('shop.product', compact('product', 'relatedProducts'));
    }
}
