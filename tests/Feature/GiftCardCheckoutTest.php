<?php

namespace Tests\Feature;

use App\Models\GiftCard;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GiftCardCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private ShippingMethod $shipping;

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

        $product = Product::factory()->create(['price' => 20.00]);
        $this->variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_qty' => 5,
        ]);

        session(['cart' => [
            'key1' => [
                'row_key' => 'key1',
                'product_id' => $product->id,
                'variant_id' => $this->variant->id,
                'sku' => 'SKU1',
                'name' => $product->name,
                'variant_label' => '',
                'personalization_text' => null,
                'personalization_price' => 0.0,
                'price' => 20.00,
                'qty' => 1,
                'image_url' => null,
            ],
        ]]);
    }

    private function mockStripe(int $amountReceived): void
    {
        $this->mock(StripeService::class)
            ->shouldReceive('verifyPaymentIntent')
            ->andReturn((object) [
                'status' => 'succeeded',
                'id' => 'pi_test_123',
                'currency' => 'usd',
                'amount_received' => $amountReceived,
            ]);
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
    public function applying_a_valid_gift_card_stores_it_in_session(): void
    {
        GiftCard::factory()->balance(25)->create(['code' => 'GC-AAAA-BBBB']);

        $response = $this->post(route('cart.gift-card'), ['code' => 'gc-aaaa-bbbb']);

        $response->assertRedirect();
        $response->assertSessionHas('gift_card', 'GC-AAAA-BBBB');
    }

    #[Test]
    public function applying_an_empty_or_invalid_gift_card_is_rejected(): void
    {
        GiftCard::factory()->balance(0)->create(['code' => 'GC-ZERO-ZERO']);

        $this->post(route('cart.gift-card'), ['code' => 'GC-ZERO-ZERO'])
            ->assertSessionHasErrors('gift_card');
        $this->post(route('cart.gift-card'), ['code' => 'GC-NOPE-NOPE'])
            ->assertSessionHasErrors('gift_card');

        $this->assertNull(session('gift_card'));
    }

    #[Test]
    public function partial_gift_card_reduces_the_amount_charged_to_stripe(): void
    {
        // Total = $20 + $5 shipping = $25. Gift card $10 => Stripe charges $15 (1500c).
        $card = GiftCard::factory()->balance(10)->create();
        session(['gift_card' => $card->code]);
        $this->mockStripe(amountReceived: 1500);

        $response = $this->postCheckout();

        $response->assertRedirect();
        $this->assertDatabaseCount('orders', 1);

        $order = Order::first();
        $this->assertSame('25.00', $order->total);
        $this->assertSame('10.00', $order->gift_card_amount);
        $this->assertSame($card->id, $order->gift_card_id);
        $this->assertSame('pi_test_123', $order->stripe_payment_intent_id);

        // Card drained, ledger recorded.
        $this->assertSame('0.00', $card->fresh()->balance);
        $this->assertDatabaseHas('gift_card_transactions', [
            'gift_card_id' => $card->id,
            'order_id' => $order->id,
            'type' => 'redeem',
            'amount' => '-10.00',
        ]);
    }

    #[Test]
    public function gift_card_covering_full_total_creates_order_with_no_stripe_charge(): void
    {
        // Gift card $30 >= $25 total => nothing payable, no PaymentIntent required.
        $card = GiftCard::factory()->balance(30)->create();
        session(['gift_card' => $card->code]);

        // No Stripe mock: verifyPaymentIntent must not be called on this path.
        $response = $this->postCheckout(['payment_intent_id' => null]);

        $response->assertRedirect();
        $this->assertDatabaseCount('orders', 1);

        $order = Order::first();
        $this->assertSame('25.00', $order->total);
        $this->assertSame('25.00', $order->gift_card_amount);
        $this->assertNull($order->stripe_payment_intent_id);

        // $30 - $25 = $5 remaining on the card.
        $this->assertSame('5.00', $card->fresh()->balance);
    }

    #[Test]
    public function stripe_amount_must_match_the_post_gift_card_payable(): void
    {
        // Gift card $10 => payable should be $15 (1500c). Stripe reports $25 (stale).
        $card = GiftCard::factory()->balance(10)->create();
        session(['gift_card' => $card->code]);
        $this->mockStripe(amountReceived: 2500);

        $response = $this->postCheckout();

        $response->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('orders', 0);
        // Balance untouched because the order never committed.
        $this->assertSame('10.00', $card->fresh()->balance);
    }
}
