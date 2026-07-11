<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * F9 — OpenAI Agentic Commerce Protocol (ACP) product feed (JSON), built from
 * the live catalog model. Covers contract assertions A23, A25, A26.
 */
class OpenAiAcpFeedTest extends TestCase
{
    use RefreshDatabase;

    private function feedItems(): array
    {
        $response = $this->get(route('feeds.acp'));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/json',
            $response->headers->get('Content-Type') ?? ''
        );

        // Body must parse as valid JSON (A23).
        $decoded = json_decode($response->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('products', $decoded);
        $this->assertIsArray($decoded['products']);

        return $decoded['products'];
    }

    #[Test]
    public function feed_returns_json_with_all_required_fields_on_every_item(): void
    {
        // A23 — required fields on every item.
        $product = Product::factory()->create([
            'name' => 'America 250 Tumbler',
            'price' => 25.00,
            'status' => 'active',
        ]);
        ProductVariant::factory()->withMpn('TTC-A250-STEEL')->create([
            'product_id' => $product->id,
            'stock_qty' => 10,
        ]);

        $items = $this->feedItems();
        $this->assertNotEmpty($items);

        foreach ($items as $item) {
            foreach (['id', 'title', 'description', 'price', 'currency', 'availability', 'image_link', 'link', 'brand', 'mpn'] as $key) {
                $this->assertArrayHasKey($key, $item, "Missing feed field: {$key}");
                $this->assertNotSame('', trim((string) $item[$key]), "Empty feed field: {$key}");
            }

            $this->assertSame('USD', $item['currency']);
            $this->assertSame('Timber Trace Crafts', $item['brand']);
            // Absolute product URL.
            $this->assertStringStartsWith('http', $item['link']);
            $this->assertStringContainsString('/product/', $item['link']);
            // Absolute image link.
            $this->assertStringStartsWith('http', $item['image_link']);
            // Price shaped "N.NN".
            $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $item['price']);
        }

        $tumbler = collect($items)->firstWhere('title', 'America 250 Tumbler');
        $this->assertNotNull($tumbler);
        $this->assertSame('25.00', $tumbler['price']);
        $this->assertSame('in_stock', $tumbler['availability']);
        $this->assertSame('TTC-A250-STEEL', $tumbler['mpn']);
    }

    #[Test]
    public function feed_excludes_drafts_and_includes_out_of_stock_as_out_of_stock(): void
    {
        // A25 — active only; OOS active product still appears (not omitted).
        $draft = Product::factory()->create([
            'name' => 'Hidden Draft Product',
            'status' => 'draft',
        ]);
        ProductVariant::factory()->create(['product_id' => $draft->id, 'stock_qty' => 5]);

        $inStock = Product::factory()->create([
            'name' => 'In Stock Keepsake',
            'status' => 'active',
        ]);
        ProductVariant::factory()->create(['product_id' => $inStock->id, 'stock_qty' => 7]);

        $oos = Product::factory()->create([
            'name' => 'Sold Out Keepsake',
            'status' => 'active',
        ]);
        ProductVariant::factory()->outOfStock()->create(['product_id' => $oos->id]);

        $items = collect($this->feedItems());
        $titles = $items->pluck('title');

        $this->assertFalse($titles->contains('Hidden Draft Product'), 'Draft product leaked into feed');
        $this->assertTrue($titles->contains('In Stock Keepsake'));
        $this->assertTrue($titles->contains('Sold Out Keepsake'), 'OOS product silently omitted');

        $this->assertSame('in_stock', $items->firstWhere('title', 'In Stock Keepsake')['availability']);
        $this->assertSame('out_of_stock', $items->firstWhere('title', 'Sold Out Keepsake')['availability']);
    }

    #[Test]
    public function feed_price_uses_sale_price_and_matches_the_product_page(): void
    {
        // A26 — canonical current price (sale when on sale), no cross-surface
        // contradiction with the product page.
        $product = Product::factory()->create([
            'name' => 'On Sale Gift',
            'slug' => 'on-sale-gift',
            'price' => 40.00,
            'sale_price' => 29.50,
            'status' => 'active',
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 3]);

        $item = collect($this->feedItems())->firstWhere('title', 'On Sale Gift');
        $this->assertNotNull($item);
        $this->assertSame('29.50', $item['price']);

        // The product page renders the same canonical current price.
        $pageHtml = $this->get(route('product.show', $product->slug))->assertOk()->getContent();
        $this->assertStringContainsString('$29.50', $pageHtml);
    }
}
