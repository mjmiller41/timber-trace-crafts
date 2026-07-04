<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class GalleryController extends Controller
{
    /**
     * Portfolio / gallery of finished pieces. Aggregates product photography
     * (and any curated customer review photos) into a single visual grid that
     * feeds discovery and gives search engines an image-rich, internally-linked
     * landing page. Every tile links back to the product it depicts.
     */
    public function index(): View
    {
        // Cap images per product so a single heavily-photographed listing can't
        // dominate the grid, and cap the total for a fast, single-viewport load.
        $perProduct = 4;
        $maxItems = 120;

        $products = Product::query()
            ->with([
                'media' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
                'media.media',
                'reviews' => fn ($q) => $q->where('status', 'approved')->whereNotNull('etsy_image_url'),
            ])
            ->where('status', 'active')
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->latest()
            ->get();

        $items = collect();

        foreach ($products as $product) {
            $taken = 0;

            foreach ($product->media as $productMedia) {
                if ($taken >= $perProduct) {
                    break;
                }

                $url = $productMedia->media?->url();
                if (! $url) {
                    continue;
                }

                $items->push([
                    'url' => $url,
                    'alt' => $productMedia->alt_text ?: $product->name,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'kind' => 'product',
                ]);
                $taken++;
            }

            // Curated customer photos read as real-world "finished pieces in the
            // wild" — a strong portfolio signal.
            foreach ($product->reviews as $review) {
                if (! $review->hasImage()) {
                    continue;
                }

                $items->push([
                    'url' => $review->image_url,
                    'alt' => 'Customer photo of '.$product->name,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'kind' => 'customer',
                ]);
            }
        }

        $items = $items->take($maxItems)->values();

        return view('gallery.index', compact('items'));
    }
}
