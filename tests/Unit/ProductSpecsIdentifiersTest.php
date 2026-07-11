<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A13 — New persistence for specs and identifiers migrates cleanly on the
 * in-memory sqlite test DB, casts correctly, and the per-variant mpn falls
 * back to the variant SKU when unset. Factories support the new fields.
 */
class ProductSpecsIdentifiersTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_table_gains_specs_gtin13_and_identifier_exists_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'specs'));
        $this->assertTrue(Schema::hasColumn('products', 'gtin13'));
        $this->assertTrue(Schema::hasColumn('products', 'identifier_exists'));
    }

    public function test_variants_table_gains_mpn_column(): void
    {
        $this->assertTrue(Schema::hasColumn('product_variants', 'mpn'));
    }

    public function test_specs_column_casts_to_array_and_round_trips(): void
    {
        $specs = [
            'material' => 'Walnut',
            'weight' => ['value' => 0.5, 'unit' => 'lb'],
            'dimensions' => ['value' => '2 x 2 x 4', 'unit' => 'in'],
            'capacity' => ['value' => 12, 'unit' => 'oz'],
        ];

        $product = Product::factory()->create(['specs' => $specs]);

        $fresh = Product::find($product->id);

        $this->assertIsArray($fresh->specs);
        $this->assertSame($specs, $fresh->specs);
        $this->assertSame('Walnut', $fresh->specs['material']);
    }

    public function test_gtin13_and_identifier_exists_persist_and_cast(): void
    {
        $product = Product::factory()->withGtin('0123456789012')->create();
        $fresh = Product::find($product->id);

        $this->assertSame('0123456789012', $fresh->gtin13);
        $this->assertTrue($fresh->identifier_exists);
        $this->assertIsBool($fresh->identifier_exists);

        $handmade = Product::factory()->withoutIdentifiers()->create();
        $freshHandmade = Product::find($handmade->id);

        $this->assertNull($freshHandmade->gtin13);
        $this->assertFalse($freshHandmade->identifier_exists);
        $this->assertIsBool($freshHandmade->identifier_exists);
    }

    public function test_variant_mpn_persists_when_set(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()
            ->withMpn('TTC-WAL-001')
            ->create(['product_id' => $product->id, 'sku' => 'SKU-123']);

        $this->assertSame('TTC-WAL-001', $variant->fresh()->mpn);
        $this->assertSame('TTC-WAL-001', $variant->fresh()->resolvedMpn());
    }

    public function test_variant_mpn_falls_back_to_sku_when_unset(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()
            ->create(['product_id' => $product->id, 'sku' => 'SKU-FALLBACK', 'mpn' => null]);

        $this->assertNull($variant->fresh()->mpn);
        $this->assertSame('SKU-FALLBACK', $variant->fresh()->resolvedMpn());
    }

    public function test_specs_default_null_emits_no_array_for_bare_product(): void
    {
        $product = Product::factory()->create();

        $this->assertNull($product->fresh()->specs);
    }

    public function test_factory_with_specs_state_populates_expected_keys(): void
    {
        $product = Product::factory()->withSpecs()->create();
        $specs = $product->fresh()->specs;

        $this->assertArrayHasKey('material', $specs);
        $this->assertArrayHasKey('weight', $specs);
        $this->assertArrayHasKey('dimensions', $specs);
        $this->assertArrayHasKey('capacity', $specs);
    }
}
