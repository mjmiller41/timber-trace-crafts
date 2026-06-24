<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    private function seedCart(float $price = 50.00, int $qty = 2): void
    {
        $product = Product::factory()->create(['price' => $price]);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        session(['cart' => [
            'abc123' => [
                'row_key' => 'abc123',
                'product_id' => $product->id,
                'variant_id' => 1,
                'name' => $product->name,
                'price' => $price,
                'personalization_price' => 0.0,
                'qty' => $qty,
            ],
        ]]);
    }

    public function test_valid_coupon_is_stored_in_session(): void
    {
        $this->seedCart();
        Coupon::factory()->create(['code' => 'SAVE10']);

        $response = $this->post(route('cart.coupon'), ['code' => 'save10']);

        $response->assertRedirect();
        $response->assertSessionHas('coupon', 'SAVE10');
        $response->assertSessionHas('success');
    }

    public function test_invalid_coupon_code_returns_error(): void
    {
        $this->seedCart();

        $response = $this->post(route('cart.coupon'), ['code' => 'BADCODE']);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['code']);
        $this->assertNull(session('coupon'));
    }

    public function test_expired_coupon_returns_error(): void
    {
        $this->seedCart();
        Coupon::factory()->expired()->create(['code' => 'EXPIRED']);

        $response = $this->post(route('cart.coupon'), ['code' => 'EXPIRED']);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['code']);
    }

    public function test_inactive_coupon_returns_error(): void
    {
        $this->seedCart();
        Coupon::factory()->inactive()->create(['code' => 'INACTIVE']);

        $response = $this->post(route('cart.coupon'), ['code' => 'INACTIVE']);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['code']);
    }

    public function test_coupon_below_minimum_order_returns_error(): void
    {
        // Cart subtotal is $100 (50 * 2); minimum $150 should fail
        $this->seedCart();
        Coupon::factory()->withMinimum(150)->create(['code' => 'HIGHMIN']);

        $response = $this->post(route('cart.coupon'), ['code' => 'HIGHMIN']);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['code']);
    }

    public function test_removing_coupon_redirects_with_success(): void
    {
        $response = $this->withSession(['coupon' => 'SAVE10'])
            ->delete(route('cart.coupon.remove'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_percent_coupon_calculates_discount(): void
    {
        $coupon = Coupon::factory()->make(['type' => 'percent', 'value' => 20, 'applies_to' => 'all']);

        $cart = [
            'a' => ['product_id' => 1, 'price' => 50.00, 'personalization_price' => 0.0, 'qty' => 2],
        ];

        $this->assertEquals(20.00, $coupon->calculateDiscount($cart));
    }

    public function test_fixed_coupon_is_capped_at_eligible_subtotal(): void
    {
        $coupon = Coupon::factory()->fixed(200)->make(['applies_to' => 'all']);

        $cart = [
            'a' => ['product_id' => 1, 'price' => 30.00, 'personalization_price' => 0.0, 'qty' => 1],
        ];

        $this->assertEquals(30.00, $coupon->calculateDiscount($cart));
    }
}
