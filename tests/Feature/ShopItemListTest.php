<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * F6 — /shop ItemList JSON-LD (incl. category filters) plus the product-card
 * out-of-stock indicator and static server-rendered card prices. Covers
 * contract assertions A9, A17, A18.
 */
class ShopItemListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pull the ItemList JSON-LD object out of the rendered /shop page, or null
     * when no ItemList script is present.
     */
    private function itemListJsonLd(string $html): ?array
    {
        preg_match_all(
            '#<script type="application/ld\+json">(.*?)</script>#s',
            $html,
            $matches
        );

        foreach ($matches[1] as $json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded) && ($decoded['@type'] ?? null) === 'ItemList') {
                return $decoded;
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // A9 / A18 — product card OOS indicator + static server-rendered price
    // ------------------------------------------------------------------

    #[Test]
    public function out_of_stock_card_shows_price_and_a_sold_out_indicator(): void
    {
        // A9 + A18. Fully out-of-stock: every variant has zero stock.
        $product = Product::factory()->create([
            'name' => 'Sold Out Keepsake',
            'price' => 42.00,
            'status' => 'active',
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 0]);

        $html = $this->get(route('shop'))->assertOk()->getContent();

        // Static, server-rendered price text (A18) — present in raw HTML.
        $this->assertStringContainsString('$42.00', $html);
        // OOS indicator (A9).
        $this->assertStringContainsString('Out of Stock', $html);
        $this->assertStringContainsString('Sold Out', $html);
    }

    #[Test]
    public function in_stock_card_shows_price_but_no_out_of_stock_indicator(): void
    {
        // A9 — the defect where method_exists() silently always rendered
        // in-stock is gone; an in-stock model shows NO indicator, an OOS one
        // does. Assert on a page that contains ONLY an in-stock product.
        $product = Product::factory()->create([
            'name' => 'Available Studs',
            'price' => 15.00,
            'status' => 'active',
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 9]);

        $html = $this->get(route('shop'))->assertOk()->getContent();

        // Static price still rendered (A18).
        $this->assertStringContainsString('$15.00', $html);
        // No OOS indicator anywhere on a page of only in-stock products (A9).
        $this->assertStringNotContainsString('Out of Stock', $html);
        $this->assertStringNotContainsString('Sold Out', $html);
    }

    #[Test]
    public function card_oos_flag_distinguishes_two_products_on_the_same_page(): void
    {
        // A9 — both cards on one page; only the OOS one carries the indicator.
        $inStock = Product::factory()->create([
            'name' => 'Fresh Stock Box',
            'price' => 30.00,
            'status' => 'active',
        ]);
        ProductVariant::factory()->create(['product_id' => $inStock->id, 'stock_qty' => 4]);

        $oos = Product::factory()->create([
            'name' => 'Empty Shelf Box',
            'price' => 55.00,
            'status' => 'active',
        ]);
        ProductVariant::factory()->create(['product_id' => $oos->id, 'stock_qty' => 0]);

        $html = $this->get(route('shop'))->assertOk()->getContent();

        // Both prices are server-rendered (A18).
        $this->assertStringContainsString('$30.00', $html);
        $this->assertStringContainsString('$55.00', $html);
        // Exactly one "Out of Stock" indicator (the OOS product's card).
        $this->assertSame(1, substr_count($html, 'Out of Stock'));
    }

    // ------------------------------------------------------------------
    // A17 — /shop ItemList JSON-LD
    // ------------------------------------------------------------------

    #[Test]
    public function shop_emits_item_list_with_name_url_price_and_availability(): void
    {
        // A17.
        $a = Product::factory()->create(['name' => 'Alpha Gift', 'price' => 25.00, 'status' => 'active']);
        ProductVariant::factory()->create(['product_id' => $a->id, 'stock_qty' => 3]);

        $b = Product::factory()->create(['name' => 'Beta Gift', 'price' => 45.00, 'status' => 'active']);
        ProductVariant::factory()->create(['product_id' => $b->id, 'stock_qty' => 0]);

        $html = $this->get(route('shop'))->assertOk()->getContent();
        $list = $this->itemListJsonLd($html);

        $this->assertNotNull($list, 'An ItemList JSON-LD block must be present.');
        $this->assertCount(2, $list['itemListElement']);

        // Index entries by product name for order-independent assertions.
        $byName = [];
        foreach ($list['itemListElement'] as $entry) {
            $item = $entry['item'];
            $byName[$item['name']] = $entry;

            // Each entry carries name, url, price and availability.
            $this->assertNotEmpty($item['name']);
            $this->assertNotEmpty($item['url']);
            $this->assertArrayHasKey('price', $item['offers']);
            $this->assertArrayHasKey('availability', $item['offers']);
        }

        $this->assertSame('25.00', $byName['Alpha Gift']['item']['offers']['price']);
        $this->assertSame('https://schema.org/InStock', $byName['Alpha Gift']['item']['offers']['availability']);
        $this->assertStringContainsString('/product/'.$a->slug, $byName['Alpha Gift']['item']['url']);

        $this->assertSame('45.00', $byName['Beta Gift']['item']['offers']['price']);
        $this->assertSame('https://schema.org/OutOfStock', $byName['Beta Gift']['item']['offers']['availability']);
    }

    #[Test]
    public function item_list_entries_include_image_only_when_the_product_has_one(): void
    {
        // A17 — "image when the product has one".
        $product = Product::factory()->create(['name' => 'No Image Gift', 'price' => 20.00, 'status' => 'active']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 5]);

        $html = $this->get(route('shop'))->assertOk()->getContent();
        $list = $this->itemListJsonLd($html);

        $this->assertNotNull($list);
        $entry = $list['itemListElement'][0];
        // A product with no media emits no image key (rather than null/empty).
        $this->assertArrayNotHasKey('image', $entry['item']);
    }

    #[Test]
    public function category_filtered_item_list_contains_only_filtered_products(): void
    {
        // A17 — category-filtered request emits an ItemList of only those products.
        $category = Category::create(['name' => 'Boxes', 'slug' => 'boxes', 'sort_order' => 0]);

        $inCategory = Product::factory()->create([
            'name' => 'Boxed Treasure',
            'price' => 60.00,
            'status' => 'active',
            'category_id' => $category->id,
        ]);
        ProductVariant::factory()->create(['product_id' => $inCategory->id, 'stock_qty' => 2]);

        $other = Product::factory()->create([
            'name' => 'Unboxed Earrings',
            'price' => 15.00,
            'status' => 'active',
        ]);
        ProductVariant::factory()->create(['product_id' => $other->id, 'stock_qty' => 2]);

        $html = $this->get(route('shop', ['category' => 'boxes']))->assertOk()->getContent();
        $list = $this->itemListJsonLd($html);

        $this->assertNotNull($list);
        $this->assertCount(1, $list['itemListElement']);
        $this->assertSame('Boxed Treasure', $list['itemListElement'][0]['item']['name']);
    }

    #[Test]
    public function empty_shop_page_emits_no_item_list(): void
    {
        // A17 — zero products must not emit a malformed/empty-item ItemList.
        $html = $this->get(route('shop', ['search' => 'zzz-nothing-matches-zzz']))
            ->assertOk()
            ->getContent();

        $this->assertNull(
            $this->itemListJsonLd($html),
            'A shop page with zero products must not emit an ItemList.'
        );
    }
}
