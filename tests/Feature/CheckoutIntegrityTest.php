<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckoutIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private ShippingMethod $shipping;

    private Product $product;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shipping = ShippingMethod::create([
            'name' => 'Flat Rate',
            'service_code' => 'flat_rate',
            'price_override' => 5.00,
            'active' => true,
            'sort_order' => 1,
        ]);

        $this->product = Product::factory()->create(['price' => 20.00]);
        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'stock_qty' => 5,
        ]);

        session(['cart' => [
            'key1' => [
                'row_key' => 'key1',
                'product_id' => $this->product->id,
                'variant_id' => $this->variant->id,
                'sku' => 'SKU1',
                'name' => $this->product->name,
                'variant_label' => '',
                'personalization_text' => null,
                'personalization_price' => 0.0,
                'price' => 20.00,
                'qty' => 1,
                'image_url' => null,
            ],
        ]]);
    }

    private function mockStripe(int $amountReceived = 2500): void
    {
        $intent = (object) [
            'status' => 'succeeded',
            'id' => 'pi_test_123',
            'currency' => 'usd',
            'amount_received' => $amountReceived,
        ];

        $this->mock(StripeService::class)
            ->shouldReceive('verifyPaymentIntent')
            ->andReturn($intent);
    }

    private function postCheckout(array $overrides = [])
    {
        return $this->post(route('checkout.process'), array_merge([
            'shipping_first_name' => 'Jane',
            'shipping_last_name' => 'Doe',
            'shipping_address_1' => '123 Main St',
            'shipping_city' => 'Portland',
            'shipping_state' => 'OR',
            'shipping_zip' => '97201',
            'shipping_method_id' => $this->shipping->id,
            'payment_intent_id' => 'pi_test_123',
            'guest_email' => 'jane@example.com',
        ], $overrides));
    }

    #[Test]
    public function checkout_fails_when_stripe_amount_does_not_match_order_total(): void
    {
        // Cart: $20 product + $5 shipping = $25 = 2500 cents. Mock returns 500 (underpayment).
        $this->mockStripe(amountReceived: 500);

        $response = $this->postCheckout();

        $response->assertRedirect();
        $response->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function checkout_is_blocked_for_replayed_payment_intent(): void
    {
        $this->mockStripe(amountReceived: 2500);

        $this->postCheckout();
        $this->assertDatabaseCount('orders', 1);

        // Re-seed cart (process() clears it after success)
        session(['cart' => [
            'key1' => [
                'row_key' => 'key1',
                'product_id' => $this->product->id,
                'variant_id' => $this->variant->id,
                'sku' => 'SKU1',
                'name' => $this->product->name,
                'variant_label' => '',
                'personalization_text' => null,
                'personalization_price' => 0.0,
                'price' => 20.00,
                'qty' => 1,
                'image_url' => null,
            ],
        ]]);

        $response = $this->postCheckout();

        // Should redirect to existing confirmation, not create a second order
        $this->assertDatabaseCount('orders', 1);
    }

    #[Test]
    public function checkout_fails_when_variant_is_out_of_stock(): void
    {
        $this->mockStripe(amountReceived: 2500);
        $this->variant->update(['stock_qty' => 0]);

        $response = $this->postCheckout();

        $response->assertRedirect();
        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function checkout_decrements_variant_stock(): void
    {
        $this->mockStripe(amountReceived: 2500);

        $this->postCheckout();

        $this->assertEquals(4, $this->variant->fresh()->stock_qty);
    }

    #[Test]
    public function checkout_increments_coupon_used_count_exactly_once(): void
    {
        // $20 product − $5 coupon + $5 shipping = $20 = 2000 cents.
        $coupon = Coupon::factory()->fixed(5)->create([
            'code' => 'SAVE5',
            'max_uses' => 10,
            'used_count' => 3,
        ]);
        session(['coupon' => 'SAVE5']);

        $this->mockStripe(amountReceived: 2000);

        $this->postCheckout();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', ['coupon_id' => $coupon->id]);
        $this->assertEquals(4, $coupon->fresh()->used_count);
    }

    #[Test]
    public function checkout_charges_the_current_variant_price_not_the_stale_cart_snapshot(): void
    {
        // Cart snapshot says $20, but the variant price has since changed to $35.
        $this->variant->update(['price' => 35.00]);

        // $35 + $5 shipping = $40 = 4000 cents.
        $this->mockStripe(amountReceived: 4000);

        $response = $this->postCheckout();

        $response->assertRedirect();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('order_items', ['price_snapshot' => 35.00]);
    }

    #[Test]
    public function checkout_rejects_an_inactive_shipping_method(): void
    {
        $this->shipping->update(['active' => false]);
        $this->mockStripe(amountReceived: 2000);

        $response = $this->postCheckout();

        $response->assertRedirect();
        $response->assertSessionHasErrors('shipping_method_id');
        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function guest_checkout_requires_an_email(): void
    {
        $this->mockStripe(amountReceived: 2500);

        $response = $this->postCheckout(['guest_email' => '']);

        $response->assertSessionHasErrors('guest_email');
        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function authenticated_checkout_does_not_require_a_guest_email(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->mockStripe(amountReceived: 2500);

        $response = $this->postCheckout(['guest_email' => '']);

        $response->assertRedirect();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'guest_email' => null]);
    }

    #[Test]
    public function checkout_honours_order_and_logs_overshoot_when_coupon_exhausted_mid_transaction(): void
    {
        Log::spy();

        // Valid at resolve time: used_count (4) < max_uses (5).
        $coupon = Coupon::factory()->fixed(5)->create([
            'code' => 'SAVE5',
            'max_uses' => 5,
            'used_count' => 4,
        ]);
        session(['coupon' => 'SAVE5']);

        // Simulate a concurrent checkout consuming the final use *after* this
        // request validated the coupon but *before* it locks + re-checks. The
        // Order::created event fires inside the transaction, ahead of the
        // coupon-consumption block, so this reproduces the exact race window.
        Order::created(function () use ($coupon) {
            Coupon::whereKey($coupon->id)->increment('used_count'); // 4 -> 5 (now at cap)
        });

        $this->mockStripe(amountReceived: 2000);

        $this->postCheckout();

        // The customer already paid the discounted total, so the order is honoured.
        $this->assertDatabaseCount('orders', 1);

        // The exhausted cap was detected at the locked re-check and logged.
        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message) => str_contains($message, 'Coupon max_uses exceeded'))
            ->once();

        // 4 (start) + 1 (simulated concurrent use) + 1 (this checkout) = 6.
        $this->assertEquals(6, $coupon->fresh()->used_count);
    }

    #[Test]
    public function checkout_locks_multiple_cart_lines_in_ascending_variant_id_order(): void
    {
        // Created after $this->variant, so it has a higher id. Placed first in
        // the cart array so the naive foreach would lock high-id-before-low-id.
        $higherVariant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'stock_qty' => 5,
        ]);
        $this->assertTrue($higherVariant->id > $this->variant->id);

        session(['cart' => [
            'key1' => [
                'row_key' => 'key1',
                'product_id' => $this->product->id,
                'variant_id' => $higherVariant->id,
                'sku' => 'SKU2',
                'name' => $this->product->name,
                'variant_label' => '',
                'personalization_text' => null,
                'personalization_price' => 0.0,
                'price' => 20.00,
                'qty' => 1,
                'image_url' => null,
            ],
            'key2' => [
                'row_key' => 'key2',
                'product_id' => $this->product->id,
                'variant_id' => $this->variant->id,
                'sku' => 'SKU1',
                'name' => $this->product->name,
                'variant_label' => '',
                'personalization_text' => null,
                'personalization_price' => 0.0,
                'price' => 20.00,
                'qty' => 1,
                'image_url' => null,
            ],
        ]]);

        // $20 + $20 + $5 shipping = $45 = 4500 cents.
        $this->mockStripe(amountReceived: 4500);

        $response = $this->postCheckout();

        $response->assertRedirect();
        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(4, $this->variant->fresh()->stock_qty);
        $this->assertEquals(4, $higherVariant->fresh()->stock_qty);
    }
}
