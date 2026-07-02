<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function cart_coupon_apply_is_rate_limited_after_ten_attempts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('cart.coupon'), ['code' => 'BADCODE']);
        }

        $response = $this->post(route('cart.coupon'), ['code' => 'BADCODE']);

        $response->assertStatus(429);
    }

    #[Test]
    public function contact_submit_is_rate_limited_after_five_attempts(): void
    {
        $payload = ['name' => 'Jane', 'email' => 'jane@example.com', 'message' => 'Hello there'];

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('contact.submit'), $payload);
        }

        $response = $this->post(route('contact.submit'), $payload);

        $response->assertStatus(429);
    }

    #[Test]
    public function newsletter_store_is_rate_limited_after_ten_attempts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('newsletter.store'), ['email' => 'jane@example.com']);
        }

        $response = $this->post(route('newsletter.store'), ['email' => 'jane@example.com']);

        $response->assertStatus(429);
    }

    #[Test]
    public function restock_store_is_rate_limited_after_ten_attempts(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $payload = ['email' => 'jane@example.com', 'variant_id' => $variant->id];

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('restock.store'), $payload);
        }

        $response = $this->post(route('restock.store'), $payload);

        $response->assertStatus(429);
    }
}
