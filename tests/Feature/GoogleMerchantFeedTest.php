<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * F10 — Google Merchant Center product feed (RSS 2.0 XML, `g:` namespace),
 * built from the live catalog model. Covers contract assertions A24, A25, A26.
 */
class GoogleMerchantFeedTest extends TestCase
{
    use RefreshDatabase;

    private function feedXml(): \SimpleXMLElement
    {
        $response = $this->get(route('feeds.google'));

        $response->assertOk();
        $this->assertStringContainsString(
            'xml',
            strtolower($response->headers->get('Content-Type') ?? '')
        );

        // Body must parse as well-formed XML (A24).
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response->getContent());
        libxml_use_internal_errors($previous);
        $this->assertNotFalse($xml, 'Feed body is not well-formed XML');

        // The g: namespace must be declared on the document (A24).
        $namespaces = $xml->getDocNamespaces(true);
        $this->assertArrayHasKey('g', $namespaces);
        $this->assertSame('http://base.google.com/ns/1.0', $namespaces['g']);

        return $xml;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function feedItems(): array
    {
        $xml = $this->feedXml();
        $items = [];

        foreach ($xml->channel->item as $item) {
            $g = $item->children('http://base.google.com/ns/1.0');
            $items[] = [
                'g:id' => (string) $g->id,
                'title' => (string) $item->title,
                'description' => (string) $item->description,
                'link' => (string) $item->link,
                'g:image_link' => (string) $g->image_link,
                'g:price' => (string) $g->price,
                'g:availability' => (string) $g->availability,
                'g:brand' => (string) $g->brand,
                'g:mpn' => (string) $g->mpn,
            ];
        }

        return $items;
    }

    #[Test]
    public function feed_returns_xml_with_all_required_g_namespace_fields_on_every_item(): void
    {
        // A24 — required fields on every item.
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
            foreach (['g:id', 'title', 'description', 'g:price', 'g:availability', 'g:image_link', 'link', 'g:brand', 'g:mpn'] as $key) {
                $this->assertArrayHasKey($key, $item, "Missing feed field: {$key}");
                $this->assertNotSame('', trim($item[$key]), "Empty feed field: {$key}");
            }

            $this->assertSame('Timber Trace Crafts', $item['g:brand']);
            // Absolute product URL.
            $this->assertStringStartsWith('http', $item['link']);
            $this->assertStringContainsString('/product/', $item['link']);
            // Absolute image link.
            $this->assertStringStartsWith('http', $item['g:image_link']);
            // Price shaped "N.NN USD".
            $this->assertMatchesRegularExpression('/^\d+\.\d{2} USD$/', $item['g:price']);
            // Availability token.
            $this->assertContains($item['g:availability'], ['in_stock', 'out_of_stock']);
        }

        $tumbler = collect($items)->firstWhere('title', 'America 250 Tumbler');
        $this->assertNotNull($tumbler);
        $this->assertSame('25.00 USD', $tumbler['g:price']);
        $this->assertSame('in_stock', $tumbler['g:availability']);
        $this->assertSame('TTC-A250-STEEL', $tumbler['g:mpn']);
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

        $this->assertSame('in_stock', $items->firstWhere('title', 'In Stock Keepsake')['g:availability']);
        $this->assertSame('out_of_stock', $items->firstWhere('title', 'Sold Out Keepsake')['g:availability']);
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
        $this->assertSame('29.50 USD', $item['g:price']);

        // The product page renders the same canonical current price.
        $pageHtml = $this->get(route('product.show', $product->slug))->assertOk()->getContent();
        $this->assertStringContainsString('$29.50', $pageHtml);
    }
}
