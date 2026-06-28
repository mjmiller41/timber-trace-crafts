<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_passes_per_variant_price_overrides_to_the_selector(): void
    {
        $product = Product::factory()->create([
            'price' => 5.00,
            'sale_price' => null,
            'status' => 'active',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 3.00,
            'stock_qty' => 10,
        ]);

        $response = $this->get(route('product.show', $product->slug));

        $response->assertOk();
        // The variant's override price is embedded in the variantSelector data
        // (JSON is HTML-entity-escaped inside the x-data attribute).
        $response->assertSee('"price":3');
        // The product-level pricing object is passed for the no-override fallback.
        $response->assertSee('"currentPrice":5');
    }
}
