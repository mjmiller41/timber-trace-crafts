<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
