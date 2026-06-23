<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(string $slug): View
    {
        $product = Product::with([
            'category',
            'variants' => fn ($q) => $q->orderBy('sort_order'),
            'media' => fn ($q) => $q->orderBy('sort_order'),
            'media.media',
            'tags',
            'reviews' => fn ($q) => $q->where('status', 'approved')->latest()->limit(10),
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
