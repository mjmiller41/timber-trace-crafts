<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A10 — the Product stock helper is the SINGLE source of availability across
 * every surface. ProductStockTest proves the helper in isolation; this proves
 * that all five consumers actually derive from it: flipping the underlying
 * stock must move availability *identically* on the product page, the /shop
 * ItemList JSON-LD, the OpenAI ACP feed, and the Google Merchant feed — and the
 * zero-variant edge case must read out-of-stock everywhere. If any surface
 * re-derived availability inline, one of these assertions would diverge.
 */
class AvailabilityConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $name, int $stock): Product
    {
        $product = Product::factory()->create([
            'name' => $name,
            'price' => 25.00,
            'status' => 'active',
        ]);
        ProductVariant::factory()->withMpn('TTC-'.$stock)->create([
            'product_id' => $product->id,
            'stock_qty' => $stock,
        ]);

        return $product->fresh();
    }

    /** Read the Product JSON-LD offer availability from a rendered product page. */
    private function productPageJsonLdAvailability(string $html): ?string
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        foreach ($m[1] as $json) {
            $decoded = json_decode($json, true); // json_decode normalizes escaped slashes
            if (is_array($decoded) && ($decoded['@type'] ?? null) === 'Product') {
                $offers = $decoded['offers'] ?? null;
                if (is_array($offers)) {
                    return $offers['availability'] ?? ($offers[0]['availability'] ?? null);
                }
            }
        }

        return null;
    }

    /** Pull the ItemList JSON-LD offer availability for a given product URL from /shop. */
    private function shopItemAvailability(string $productSlug): ?string
    {
        $html = $this->get(route('shop'))->getContent();
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        foreach ($m[1] as $json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded) && ($decoded['@type'] ?? null) === 'ItemList') {
                foreach ($decoded['itemListElement'] ?? [] as $li) {
                    $offers = $li['item']['offers'] ?? $li['offers'] ?? null;
                    $url = $li['url'] ?? ($li['item']['url'] ?? '');
                    if ($offers && str_contains((string) $url, '/product/'.$productSlug)) {
                        return $offers['availability'] ?? null;
                    }
                }
            }
        }

        return null;
    }

    private function acpAvailability(string $productSlug): ?string
    {
        $products = json_decode($this->get(route('feeds.acp'))->getContent(), true)['products'] ?? [];
        foreach ($products as $item) {
            if (str_contains((string) ($item['link'] ?? ''), '/product/'.$productSlug)) {
                return $item['availability'] ?? null;
            }
        }

        return null;
    }

    private function merchantAvailability(string $productSlug): ?string
    {
        $xml = $this->get(route('feeds.google'))->getContent();
        // Match the <item> whose <link> contains this product's URL, then read g:availability.
        if (preg_match('#<item>(?:(?!</item>).)*?/product/'.preg_quote($productSlug, '#').'.*?</item>#s', $xml, $item)
            && preg_match('#<g:availability>(.*?)</g:availability>#', $item[0], $avail)) {
            return trim($avail[1]);
        }

        return null;
    }

    #[Test]
    public function every_surface_reports_in_stock_when_the_helper_is_in_stock(): void
    {
        $product = $this->makeProduct('Consistency In Stock', 8);
        $this->assertTrue($product->isInStock());

        // Product page: visible server-rendered state + Product JSON-LD availability.
        $html = $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertSee('In Stock')
            ->getContent();
        $this->assertSame('https://schema.org/InStock', $this->productPageJsonLdAvailability($html));

        // The three catalog surfaces, all derived from the same helper.
        $this->assertSame('https://schema.org/InStock', $this->shopItemAvailability($product->slug));
        $this->assertSame('in_stock', $this->acpAvailability($product->slug));
        $this->assertSame('in_stock', $this->merchantAvailability($product->slug));
    }

    #[Test]
    public function flipping_stock_to_zero_moves_every_surface_to_out_of_stock_together(): void
    {
        $product = $this->makeProduct('Consistency Flip', 5);

        // Flip the single stock source to zero.
        $product->variants()->update(['stock_qty' => 0]);
        $product = $product->fresh();
        $this->assertTrue($product->isOutOfStock());

        // Product page: OOS text, canonical price still visible (never hidden).
        $html = $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertSee('Out of Stock')
            ->assertSee('25.00')
            ->getContent();
        $this->assertSame('https://schema.org/OutOfStock', $this->productPageJsonLdAvailability($html));

        // All three catalog surfaces flip in lockstep.
        $this->assertSame('https://schema.org/OutOfStock', $this->shopItemAvailability($product->slug));
        $this->assertSame('out_of_stock', $this->acpAvailability($product->slug));
        $this->assertSame('out_of_stock', $this->merchantAvailability($product->slug));
    }

    #[Test]
    public function a_product_with_no_variants_reads_out_of_stock_on_every_surface(): void
    {
        // Zero-variant edge case — the round-1 failure mode A10 calls out.
        $product = Product::factory()->create([
            'name' => 'No Variants Edge',
            'price' => 25.00,
            'status' => 'active',
        ]);
        $this->assertTrue($product->fresh()->isOutOfStock());

        $this->assertSame('https://schema.org/OutOfStock', $this->shopItemAvailability($product->slug));
        $this->assertSame('out_of_stock', $this->acpAvailability($product->slug));
        $this->assertSame('out_of_stock', $this->merchantAvailability($product->slug));
    }
}
