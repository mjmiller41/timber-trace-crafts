<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * F4 — Product JSON-LD upgrade. Covers contract assertions A8, A11, A12, A14,
 * A15, A16: full untruncated description, additionalProperty specs with
 * well-known top-level mappings, literal brand, per-variant stable mpn,
 * gtin13/identifierExists, and correct InStock/OutOfStock availability.
 */
class ProductJsonLdTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pull the Product JSON-LD object out of the rendered page.
     */
    private function productJsonLd(string $html): array
    {
        preg_match_all(
            '#<script type="application/ld\+json">(.*?)</script>#s',
            $html,
            $matches
        );

        foreach ($matches[1] as $json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded) && ($decoded['@type'] ?? null) === 'Product') {
                return $decoded;
            }
        }

        $this->fail('No Product JSON-LD block found in the response.');
    }

    #[Test]
    public function offer_availability_is_out_of_stock_when_total_variant_stock_is_zero(): void
    {
        // A8.
        $product = Product::factory()->create(['status' => 'active']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 0]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 0]);

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();
        $schema = $this->productJsonLd($html);

        $this->assertSame(
            'https://schema.org/OutOfStock',
            $schema['offers']['availability'],
            'A fully OOS product must have OutOfStock offer availability.'
        );
        // Matches the HTML state on the same response.
        $this->assertStringContainsString('Out of Stock', $html);
    }

    #[Test]
    public function offer_availability_is_in_stock_when_any_variant_has_stock(): void
    {
        // A8.
        $product = Product::factory()->create(['status' => 'active']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 0]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 7]);

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();
        $schema = $this->productJsonLd($html);

        $this->assertSame(
            'https://schema.org/InStock',
            $schema['offers']['availability'],
            'A product with any in-stock variant must have InStock offer availability.'
        );
    }

    #[Test]
    public function description_is_the_full_untruncated_strip_tagged_description(): void
    {
        // A11 — description longer than 500 chars.
        $tail = 'THE-UNIQUE-TAIL-MARKER-AT-THE-VERY-END';
        $longBody = str_repeat('This handmade piece is crafted with precision and warmth. ', 12);
        $description = '<p>'.$longBody.$tail.'</p>';
        $this->assertGreaterThan(500, strlen(strip_tags($description)));

        $product = Product::factory()->create([
            'status' => 'active',
            'description' => $description,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 3]);

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();
        $schema = $this->productJsonLd($html);

        // Contains the tail — no truncation.
        $this->assertStringContainsString($tail, $schema['description']);
        // No truncation ellipsis.
        $this->assertStringNotContainsString('...', $schema['description']);
        $this->assertStringNotContainsString("\u{2026}", $schema['description']);
        $this->assertStringNotContainsString('…', $schema['description']);

        // Longer than and different from the 155-char truncated meta preview.
        $preview = \Illuminate\Support\Str::limit(strip_tags($description), 155);
        $this->assertNotSame($preview, $schema['description']);
        $this->assertGreaterThan(strlen($preview), strlen($schema['description']));
    }

    #[Test]
    public function specs_are_emitted_as_additional_properties_with_top_level_mappings(): void
    {
        // A12.
        $product = Product::factory()->withSpecs([
            'material' => 'Walnut',
            'weight' => ['value' => 0.8, 'unit' => 'lb'],
            'dimensions' => ['value' => '3.5 x 3.5 x 7', 'unit' => 'in'],
            'capacity' => ['value' => 20, 'unit' => 'oz'],
        ])->create(['status' => 'active']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 5]);

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();
        $schema = $this->productJsonLd($html);

        $this->assertArrayHasKey('additionalProperty', $schema);
        $props = collect($schema['additionalProperty'])->keyBy('name');

        $this->assertSame('PropertyValue', $props['Material']['@type']);
        $this->assertSame('Walnut', $props['Material']['value']);

        $this->assertSame('lb', $props['Weight']['unitText']);
        $this->assertEquals(0.8, $props['Weight']['value']);

        $this->assertSame('oz', $props['Capacity']['unitText']);
        $this->assertEquals(20, $props['Capacity']['value']);

        // Well-known keys mapped to top-level schema fields.
        $this->assertSame('Walnut', $schema['material']);
        $this->assertSame('QuantitativeValue', $schema['weight']['@type']);
        $this->assertEquals(0.8, $schema['weight']['value']);
        $this->assertSame('lb', $schema['weight']['unitText']);
        $this->assertSame('3.5 x 3.5 x 7 in', $schema['size']);
    }

    #[Test]
    public function a_product_with_no_specs_emits_no_additional_property_array(): void
    {
        // A12.
        $product = Product::factory()->create(['status' => 'active', 'specs' => null]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 5]);

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();
        $schema = $this->productJsonLd($html);

        $this->assertArrayNotHasKey('additionalProperty', $schema);
    }

    #[Test]
    public function brand_is_the_literal_brand_name_not_the_settings_store_name(): void
    {
        // A14 — even when the store.name setting differs, brand stays literal.
        \App\Models\Setting::set('store.name', 'A Totally Different Store Name');
        \Illuminate\Support\Facades\Cache::forget('setting.store.name');

        $product = Product::factory()->create(['status' => 'active']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 5]);

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();
        $schema = $this->productJsonLd($html);

        $this->assertSame(
            ['@type' => 'Brand', 'name' => 'Timber Trace Crafts'],
            $schema['brand']
        );
    }

    #[Test]
    public function every_variant_persistent_mpn_appears_in_the_json_ld(): void
    {
        // A15.
        $product = Product::factory()->create(['status' => 'active']);
        $withMpn = ProductVariant::factory()
            ->withMpn('TTC-MPN-001')
            ->create(['product_id' => $product->id, 'stock_qty' => 5]);
        // No explicit mpn -> falls back to SKU, never the bare id.
        $skuFallback = ProductVariant::factory()
            ->create(['product_id' => $product->id, 'stock_qty' => 2, 'sku' => 'SKU-FALLBACK-9']);

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();
        $schema = $this->productJsonLd($html);

        $mpns = collect($schema['offers']['offers'])->pluck('mpn')->all();

        $this->assertContains('TTC-MPN-001', $mpns);
        $this->assertContains('SKU-FALLBACK-9', $mpns);
        // Never a bare auto-increment id.
        $this->assertNotContains((string) $skuFallback->id, $mpns);
        $this->assertNotContains($skuFallback->id, $mpns);
    }

    #[Test]
    public function a_product_with_a_known_gtin13_emits_gtin13_and_not_identifier_exists(): void
    {
        // A16 (tumbler-blank case).
        $product = Product::factory()
            ->withGtin('0123456789012')
            ->create(['status' => 'active']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 5]);

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();
        $schema = $this->productJsonLd($html);

        $this->assertSame('0123456789012', $schema['gtin13']);
        $this->assertArrayNotHasKey('identifierExists', $schema);
    }

    #[Test]
    public function a_handmade_product_without_identifiers_emits_identifier_exists_false(): void
    {
        // A16 — JSON boolean false, not the string "false"; never both keys.
        $product = Product::factory()
            ->withoutIdentifiers()
            ->create(['status' => 'active']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 5]);

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();
        $schema = $this->productJsonLd($html);

        $this->assertArrayHasKey('identifierExists', $schema);
        $this->assertFalse($schema['identifierExists']);
        $this->assertArrayNotHasKey('gtin13', $schema);

        // Real JSON boolean in the raw payload, not the string "false".
        $this->assertStringContainsString('"identifierExists":false', $html);
    }
}
