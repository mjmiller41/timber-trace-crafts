<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\ProductFeedPresenter;
use Illuminate\Http\JsonResponse;

class FeedController extends Controller
{
    /**
     * OpenAI Agentic Commerce Protocol (ACP) product feed (JSON).
     *
     * Built entirely from the live catalog model via {@see ProductFeedPresenter}
     * — active products only, out-of-stock ones included as out_of_stock, no
     * outbound HTTP. Every item carries the required agentic-commerce fields
     * (id, title, description, price + currency, availability, image link,
     * absolute product URL, brand, mpn). (A23, A25, A26)
     */
    public function acp(): JsonResponse
    {
        $items = ProductFeedPresenter::activeProducts()
            ->map(fn (Product $product) => ProductFeedPresenter::for($product)->acpItem())
            ->values()
            ->all();

        return response()->json([
            'version' => '1.0',
            'feed_type' => 'openai_acp_product_feed',
            'brand' => Product::BRAND,
            'currency' => 'USD',
            'target_countries' => ['US'],
            'generated_at' => now()->toIso8601String(),
            'product_count' => count($items),
            'products' => $items,
        ]);
    }
}
