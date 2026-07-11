<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A10 — Product stock/availability helper is the single source of truth.
 *
 * Covers isOutOfStock()/isInStock()/totalStock()/availabilitySchemaUrl():
 * out-of-stock when total variant stock is 0 (including the zero-variant
 * edge case) and in-stock when any variant has stock.
 */
class ProductStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_with_no_variants_is_out_of_stock(): void
    {
        $product = Product::factory()->create();

        $this->assertSame(0, $product->totalStock());
        $this->assertTrue($product->isOutOfStock());
        $this->assertFalse($product->isInStock());
        $this->assertSame('https://schema.org/OutOfStock', $product->availabilitySchemaUrl());
    }

    public function test_product_whose_variants_all_have_zero_stock_is_out_of_stock(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->count(3)->for($product)->outOfStock()->create();

        $this->assertSame(0, $product->totalStock());
        $this->assertTrue($product->isOutOfStock());
        $this->assertFalse($product->isInStock());
        $this->assertSame('https://schema.org/OutOfStock', $product->availabilitySchemaUrl());
    }

    public function test_product_is_in_stock_when_any_variant_has_stock(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->outOfStock()->create();
        ProductVariant::factory()->for($product)->create(['stock_qty' => 7]);

        $this->assertSame(7, $product->totalStock());
        $this->assertFalse($product->isOutOfStock());
        $this->assertTrue($product->isInStock());
        $this->assertSame('https://schema.org/InStock', $product->availabilitySchemaUrl());
    }

    public function test_helper_agrees_whether_or_not_the_variants_relation_is_eager_loaded(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['stock_qty' => 4]);

        // Not loaded: falls back to a DB aggregate.
        $fresh = Product::find($product->id);
        $this->assertFalse($fresh->relationLoaded('variants'));
        $this->assertSame(4, $fresh->totalStock());
        $this->assertFalse($fresh->isOutOfStock());

        // Eager-loaded: uses the in-memory collection, same answer.
        $loaded = Product::with('variants')->find($product->id);
        $this->assertTrue($loaded->relationLoaded('variants'));
        $this->assertSame(4, $loaded->totalStock());
        $this->assertFalse($loaded->isOutOfStock());
    }
}
