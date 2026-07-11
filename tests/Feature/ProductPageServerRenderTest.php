<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * F3 — Server-rendered price and per-variant stock text on the product page.
 * The raw HTML (no JS / no Alpine) must carry the price and per-variant
 * availability as static text a text-only agent can read; Alpine hydrates the
 * same nodes. Covers contract assertions A5, A6, A7.
 */
class ProductPageServerRenderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_server_renders_the_current_price_as_static_text_for_an_in_stock_product(): void
    {
        // A5: the formatted price is present in the visible body, not only in
        // JSON-LD / x-data blobs / empty x-text nodes.
        $product = Product::factory()->create([
            'price' => 25.00,
            'sale_price' => null,
            'status' => 'active',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_qty' => 10,
        ]);

        $html = $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->getContent();

        // The dollar-sign formatted price ("$25.00") is only produced by the
        // server-rendered span — JSON-LD emits "25.00" and x-data emits 25.
        $this->assertStringContainsString('$25.00', $html);

        // It sits inside an Alpine-hydrated node (same node, not an empty one):
        // x-text binding is present AND the node's text content is the price.
        $this->assertMatchesRegularExpression(
            '/x-text="formatPrice\(displayPrice\)"[^>]*>\$25\.00</',
            $html,
            'The current price must be server-rendered as the text content of the x-text node.'
        );
    }

    #[Test]
    public function it_server_renders_both_sale_and_struck_through_original_price_when_on_sale(): void
    {
        // A5 (sale branch).
        $product = Product::factory()->create([
            'price' => 30.00,
            'sale_price' => 20.00,
            'status' => 'active',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_qty' => 8,
        ]);

        $html = $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->getContent();

        // Sale (current) price server-rendered.
        $this->assertStringContainsString('$20.00', $html);
        // Original price server-rendered inside a struck-through node.
        $this->assertMatchesRegularExpression(
            '/line-through[^>]*>\$30\.00</',
            $html,
            'The original price must be server-rendered inside the struck-through node.'
        );
    }

    #[Test]
    public function it_server_renders_each_variant_label_with_its_stock_state(): void
    {
        // A6: per-variant label + In Stock / Out of Stock as static text, with
        // Alpine hydration bindings retained on the same nodes.
        $product = Product::factory()->create(['status' => 'active']);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'label' => 'Walnut',
            'stock_qty' => 12,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'label' => 'Maple',
            'stock_qty' => 0,
        ]);

        $html = $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->getContent();

        // Both variant labels are present as static text.
        $this->assertStringContainsString('Walnut', $html);
        $this->assertStringContainsString('Maple', $html);
        // Both availability states are readable in the raw HTML.
        $this->assertStringContainsString('In Stock', $html);
        $this->assertStringContainsString('Out of Stock', $html);
        // Alpine hydration binding remains on the same (server-rendered) nodes.
        $this->assertStringContainsString('selectVariant(variants[', $html);
    }

    #[Test]
    public function it_server_renders_the_price_and_out_of_stock_text_for_a_fully_out_of_stock_product(): void
    {
        // A7: price is never hidden on OOS; visible Out of Stock text alongside it.
        $product = Product::factory()->create([
            'price' => 25.00,
            'sale_price' => null,
            'status' => 'active',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'label' => 'Oak',
            'stock_qty' => 0,
        ]);

        $html = $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->getContent();

        // Canonical price still server-rendered.
        $this->assertStringContainsString('$25.00', $html);
        // Visible Out of Stock text (server-rendered availability line).
        $this->assertStringContainsString('Out of Stock', $html);
        // And the availability line reflects the OOS state as its text content.
        $this->assertMatchesRegularExpression(
            "/x-text=\"inStock \? \(lowStock[^>]*>Out of Stock</",
            $html,
            'The availability line must server-render Out of Stock as its text content.'
        );
    }
}
