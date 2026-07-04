<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GalleryAndUxTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_gallery_page_renders_active_product_photos_linked_to_products(): void
    {
        $product = Product::factory()->create([
            'name' => 'Walnut Fern Earrings',
            'status' => 'active',
        ]);
        $media = Media::factory()->create(['disk' => 'public', 'path' => 'products/walnut-fern.png']);
        ProductMedia::create([
            'product_id' => $product->id,
            'media_id' => $media->id,
            'is_primary' => true,
            'sort_order' => 0,
            'alt_text' => 'Walnut fern earrings on linen',
        ]);

        $response = $this->get(route('gallery.index'));

        $response->assertOk();
        $response->assertSee('Gallery');
        // Image is rendered and tile links back to the product.
        $response->assertSee($media->url(), false);
        $response->assertSee(route('product.show', $product->slug), false);
        $response->assertSee('Walnut Fern Earrings');
    }

    #[Test]
    public function draft_products_are_excluded_from_the_gallery(): void
    {
        $draft = Product::factory()->create(['name' => 'Hidden Draft Piece', 'status' => 'draft']);
        $media = Media::factory()->create(['disk' => 'public', 'path' => 'products/hidden.png']);
        ProductMedia::create([
            'product_id' => $draft->id,
            'media_id' => $media->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $response = $this->get(route('gallery.index'));

        $response->assertOk();
        $response->assertDontSee('Hidden Draft Piece');
        $response->assertDontSee($media->url(), false);
    }

    #[Test]
    public function the_gallery_is_listed_in_the_sitemap(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('gallery.index'), false);
    }

    #[Test]
    public function approved_review_photos_render_on_the_product_page(): void
    {
        $product = Product::factory()->create(['name' => 'Maple Keepsake Box', 'status' => 'active']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 5]);

        $photoUrl = 'https://i.etsystatic.com/example/customer-photo.jpg';
        ProductReview::create([
            'product_id' => $product->id,
            'source' => 'etsy',
            'etsy_image_url' => $photoUrl,
            'guest_name' => 'Dana R.',
            'rating' => 5,
            'body' => 'Beautiful craftsmanship, photo attached.',
            'status' => 'approved',
        ]);

        $response = $this->get(route('product.show', $product->slug));

        $response->assertOk();
        $response->assertSee('Photos from customers');
        $response->assertSee($photoUrl, false);
        // Reviewer name accessor is used (guest name), not a raw null fallback.
        $response->assertSee('Dana R.');
    }

    #[Test]
    public function pending_review_photos_do_not_leak_onto_the_product_page(): void
    {
        $product = Product::factory()->create(['status' => 'active']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 5]);

        $photoUrl = 'https://i.etsystatic.com/example/pending-photo.jpg';
        ProductReview::create([
            'product_id' => $product->id,
            'source' => 'etsy',
            'etsy_image_url' => $photoUrl,
            'rating' => 4,
            'body' => 'Awaiting moderation.',
            'status' => 'pending',
        ]);

        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertDontSee($photoUrl, false)
            ->assertDontSee('Photos from customers');
    }

    #[Test]
    public function the_storefront_ships_the_dark_mode_toggle_and_no_flash_bootstrap(): void
    {
        // Any storefront page shares layouts/app.blade.php + the nav; the gallery
        // renders cleanly in the test env (the home view needs live R2 config).
        $response = $this->get(route('gallery.index'));

        $response->assertOk();
        // No-FOUC bootstrap applies the stored/preferred theme before paint.
        $response->assertSee('window.toggleTheme', false);
        $response->assertSee("classList.toggle('dark'", false);
        // The nav exposes a user-facing toggle control.
        $response->assertSee('theme-toggle', false);
        $response->assertSee('Toggle light or dark theme', false);
    }
}
