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

    #[Test]
    public function it_renders_the_recently_viewed_strip_with_the_current_product_card(): void
    {
        $product = Product::factory()->create([
            'name' => 'Cherry Butterfly Earrings',
            'price' => 24.00,
            'sale_price' => null,
            'status' => 'active',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_qty' => 5,
        ]);

        $response = $this->get(route('product.show', $product->slug));

        $response->assertOk();
        // The Alpine strip is present and seeded with the current product's card data
        // (JSON is HTML-entity-escaped inside the x-data attribute).
        $response->assertSee('recentlyViewed(', false);
        $response->assertSee('Recently Viewed');
        $response->assertSee('&quot;slug&quot;:&quot;'.$product->slug.'&quot;', false);
    }
}
