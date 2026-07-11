<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * F5 — Variant-selector hygiene on the product page.
 *
 * The selector heading must derive from the product's real variation type
 * (never a hardcoded "Select Wood"), a product with fewer than two variants
 * must render no selector at all, and a multi-variant product must enumerate
 * every variant label server-side. Covers contract assertions A19, A20.
 */
class ProductVariantSelectorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_non_wood_variation_type_renders_no_select_wood_and_names_its_real_type(): void
    {
        // A19: the all-stainless tumbler shape — variation type "Design", not wood.
        $product = Product::factory()->create(['status' => 'active']);
        $type = $product->variationTypes()->create(['name' => 'Design', 'sort_order' => 0]);
        foreach (['Stars', 'Eagle'] as $label) {
            ProductVariant::factory()->create([
                'product_id' => $product->id,
                'variation_type_id' => $type->id,
                'label' => $label,
                'stock_qty' => 6,
            ]);
        }

        $html = $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->getContent();

        // No hardcoded wood heading anywhere on the page.
        $this->assertStringNotContainsString('Select Wood', $html);
        // Heading derives from the real variation type.
        $this->assertStringContainsString('Select Design', $html);
    }

    #[Test]
    public function a_single_variant_product_renders_no_selector_at_all(): void
    {
        // A19: fewer than two variants => no selector heading, and no "Select Wood".
        $product = Product::factory()->create(['status' => 'active']);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'label' => 'Walnut',
            'stock_qty' => 4,
        ]);

        $html = $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Select Wood', $html);
        // The selector block is absent entirely — its heading and its
        // per-variant buttons (the unique `selectVariant(variants[` binding)
        // are not rendered.
        $this->assertStringNotContainsString('<p class="section-label">Select ', $html);
        $this->assertStringNotContainsString('selectVariant(variants[', $html);
        $this->assertFalse($product->fresh()->hasSelectableVariants());
    }

    #[Test]
    public function a_zero_variant_product_renders_no_selector(): void
    {
        // A19 edge case: no variants at all => no selector.
        $product = Product::factory()->create(['status' => 'active']);

        $html = $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Select Wood', $html);
        $this->assertStringNotContainsString('<p class="section-label">Select ', $html);
        $this->assertStringNotContainsString('selectVariant(variants[', $html);
    }

    #[Test]
    public function a_multi_style_product_enumerates_every_label_with_its_type_heading(): void
    {
        // A20: Heart Jewelry Box shape — several named style variants.
        $product = Product::factory()->create(['status' => 'active']);
        $type = $product->variationTypes()->create(['name' => 'Style', 'sort_order' => 0]);
        $labels = ['Classic', 'Monogram', 'Floral', 'Rustic'];
        foreach ($labels as $label) {
            ProductVariant::factory()->create([
                'product_id' => $product->id,
                'variation_type_id' => $type->id,
                'label' => $label,
                'stock_qty' => 5,
            ]);
        }

        $html = $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->getContent();

        // Heading names the variation type, not a hardcoded wood label.
        $this->assertStringContainsString('Select Style', $html);
        $this->assertStringNotContainsString('Select Wood', $html);

        // Every style label is server-rendered in the raw HTML.
        foreach ($labels as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    #[Test]
    public function the_variation_type_name_falls_back_to_the_etsy_variation_name(): void
    {
        // A19/A20: when no ProductVariationType record exists, the heading uses
        // the product's stored Etsy variation name rather than defaulting to wood.
        $product = Product::factory()->create([
            'status' => 'active',
            'etsy_variation_name' => 'Finish',
        ]);
        foreach (['Matte', 'Gloss'] as $label) {
            ProductVariant::factory()->create([
                'product_id' => $product->id,
                'label' => $label,
                'stock_qty' => 3,
            ]);
        }

        $this->assertSame('Finish', $product->fresh()->variationTypeName());

        $html = $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Select Finish', $html);
        $this->assertStringNotContainsString('Select Wood', $html);
    }
}
