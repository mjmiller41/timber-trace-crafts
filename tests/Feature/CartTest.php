<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_charges_the_variant_price_override_when_set(): void
    {
        $product = Product::factory()->create(['price' => 50.00, 'sale_price' => null]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 30.00,
        ]);

        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'qty' => 1,
        ]);

        $response->assertSessionHas('cart', function ($cart) {
            return (float) collect($cart)->first()['price'] === 30.0;
        });
    }

    #[Test]
    public function it_falls_back_to_product_current_price_when_variant_has_no_override(): void
    {
        $product = Product::factory()->create(['price' => 50.00, 'sale_price' => null]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => null,
        ]);

        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'qty' => 1,
        ]);

        $response->assertSessionHas('cart', function ($cart) {
            return (float) collect($cart)->first()['price'] === 50.0;
        });
    }

    #[Test]
    public function it_rejects_a_variant_that_belongs_to_a_different_product(): void
    {
        $productA = Product::factory()->create(['price' => 100.00]);
        $productB = Product::factory()->create(['price' => 10.00]);
        $variantOfB = ProductVariant::factory()->create([
            'product_id' => $productB->id,
            'price' => 10.00,
        ]);

        $response = $this->post(route('cart.add'), [
            'product_id' => $productA->id,
            'variant_id' => $variantOfB->id,
            'qty' => 1,
        ]);

        $response->assertSessionHasErrors('variant_id');
        $response->assertSessionMissing('cart');
    }

    #[Test]
    public function it_rejects_adding_a_non_active_product(): void
    {
        $product = Product::factory()->create(['status' => 'draft']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'qty' => 1,
        ]);

        $response->assertSessionHasErrors('product_id');
        $response->assertSessionMissing('cart');
    }

    #[Test]
    public function it_rejects_adding_a_disabled_variant(): void
    {
        $product = Product::factory()->create(['status' => 'active']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_enabled' => false,
        ]);

        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'qty' => 1,
        ]);

        $response->assertSessionHasErrors('product_id');
        $response->assertSessionMissing('cart');
    }

    #[Test]
    public function the_cart_page_renders_the_item_image_from_the_image_url_key(): void
    {
        $imageUrl = 'https://cdn.example.com/products/teardrop.png';
        $cart = [
            'abc123' => [
                'row_key' => 'abc123',
                'product_id' => 1,
                'variant_id' => 1,
                'sku' => 'EAR-TD-01',
                'name' => 'Teardrop Earrings',
                'variant_label' => 'Cherry',
                'personalization_text' => null,
                'personalization_price' => 0.0,
                'price' => 24.0,
                'qty' => 1,
                'image_url' => $imageUrl,
            ],
        ];

        $response = $this->withSession(['cart' => $cart])->get(route('cart.index'));

        $response->assertOk();
        // The thumbnail must read the canonical `image_url` cart-item key.
        $response->assertSee($imageUrl, false);
    }
}
