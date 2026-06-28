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
}
