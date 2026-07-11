<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ButterflyCopyTruthTest extends TestCase
{
    use RefreshDatabase;

    private function canonicalButterfly(): Product
    {
        $product = Product::factory()->create([
            'name' => 'Butterfly Earrings',
            'slug' => 'butterfly-earrings',
            'status' => 'active',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_qty' => 10,
        ]);

        return $product;
    }

    // ---- A21: config-driven 301 redirects for duplicate butterfly slugs ----

    #[Test]
    public function duplicate_gift_slugs_301_redirect_to_the_canonical_product_url(): void
    {
        $this->canonicalButterfly();
        $canonicalUrl = route('product.show', ['slug' => 'butterfly-earrings']);

        foreach (['butterfly-earrings-gift', 'butterfly-earrings-gift-2', 'butterfly-earrings-gift-3'] as $dupe) {
            $response = $this->get('/product/'.$dupe);

            $response->assertStatus(301);
            $response->assertRedirect($canonicalUrl);
        }
    }

    #[Test]
    public function the_canonical_slug_itself_returns_200_without_redirect_loop(): void
    {
        $this->canonicalButterfly();

        $response = $this->get('/product/butterfly-earrings');

        $response->assertOk();
    }

    #[Test]
    public function an_unmapped_unknown_slug_still_404s(): void
    {
        $this->canonicalButterfly();

        $response = $this->get('/product/no-such-product-xyz');

        $response->assertNotFound();
    }

    #[Test]
    public function redirect_map_is_config_driven(): void
    {
        // Redirect behaviour is driven purely by config, not hardcoded.
        config(['catalog.slug_redirects' => ['made-up-alias' => 'butterfly-earrings']]);
        $this->canonicalButterfly();

        $response = $this->get('/product/made-up-alias');

        $response->assertStatus(301);
        $response->assertRedirect(route('product.show', ['slug' => 'butterfly-earrings']));
    }

    // ---- A22: data-fix command for the teardrop copy-paste error ----

    #[Test]
    public function the_fix_rewrites_teardrop_wording_in_butterfly_products_only(): void
    {
        $butterfly = Product::factory()->create([
            'name' => 'Butterfly Earrings',
            'slug' => 'butterfly-earrings',
            'status' => 'active',
            'description' => 'These delicate earrings feature a graceful teardrop silhouette. '
                .'Each Teardrop is hand cut from premium wood.',
            'short_description' => 'Elegant teardrop earrings.',
        ]);
        ProductVariant::factory()->create(['product_id' => $butterfly->id, 'stock_qty' => 8]);

        $genuine = Product::factory()->create([
            'name' => 'Teardrop Earrings',
            'slug' => 'teardrop-earrings',
            'status' => 'active',
            'description' => 'A timeless teardrop silhouette in six wood species.',
            'short_description' => 'Classic teardrop earrings.',
        ]);
        ProductVariant::factory()->create(['product_id' => $genuine->id, 'stock_qty' => 8]);

        $this->artisan('catalog:fix-butterfly-copy')->assertSuccessful();

        $butterfly->refresh();
        $genuine->refresh();

        // Butterfly product's stray teardrop wording is corrected, case preserved.
        $this->assertStringNotContainsStringIgnoringCase('teardrop', $butterfly->description);
        $this->assertStringContainsString('butterfly silhouette', $butterfly->description);
        $this->assertStringContainsString('Butterfly is hand cut', $butterfly->description);
        $this->assertStringNotContainsStringIgnoringCase('teardrop', $butterfly->short_description);
        $this->assertStringContainsString('butterfly earrings', $butterfly->short_description);

        // The genuine Teardrop Earrings copy is untouched.
        $this->assertStringContainsString('teardrop silhouette', $genuine->description);
        $this->assertStringContainsString('Classic teardrop earrings.', $genuine->short_description);
    }

    #[Test]
    public function the_corrected_copy_is_served_on_the_product_page(): void
    {
        $butterfly = Product::factory()->create([
            'name' => 'Butterfly Earrings',
            'slug' => 'butterfly-earrings',
            'status' => 'active',
            'description' => 'Featuring a graceful teardrop silhouette cut from cherry wood.',
        ]);
        ProductVariant::factory()->create(['product_id' => $butterfly->id, 'stock_qty' => 8]);

        $this->artisan('catalog:fix-butterfly-copy')->assertSuccessful();

        $response = $this->get('/product/butterfly-earrings');

        $response->assertOk();
        $response->assertSee('butterfly silhouette');
        $response->assertDontSee('teardrop silhouette');
    }
}
