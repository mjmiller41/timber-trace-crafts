<?php

namespace Tests\Feature;

use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.stripe.webhook_secret', $this->secret);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function postEvent(array $event, ?string $signature = null): TestResponse
    {
        $payload = json_encode($event);
        $timestamp = time();
        $sig = $signature ?? 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $this->secret);

        return $this->call(
            'POST',
            '/webhooks/stripe',
            [], [], [],
            ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );
    }

    private function chargeRefundedEvent(string $paymentIntentId, int $amountRefundedCents, string $refundId = 're_test_1'): array
    {
        return [
            'id' => 'evt_test_1',
            'type' => 'charge.refunded',
            'data' => ['object' => [
                'object' => 'charge',
                'payment_intent' => $paymentIntentId,
                'amount_refunded' => $amountRefundedCents,
                'refunds' => ['data' => [['id' => $refundId]]],
            ]],
        ];
    }

    #[Test]
    public function a_dashboard_refund_is_synced_onto_the_order(): void
    {
        Mail::fake();

        $order = Order::factory()->create([
            'status' => 'processing',
            'total' => 58.00,
            'stripe_payment_intent_id' => 'pi_test_123',
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->postEvent($this->chargeRefundedEvent('pi_test_123', 5800));

        $response->assertOk();
        $order->refresh();
        $this->assertEquals('refunded', $order->status);
        $this->assertEquals('58.00', $order->refunded_amount);
        $this->assertEquals('re_test_1', $order->stripe_refund_id);
        $this->assertNotNull($order->refunded_at);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'status' => 'refunded',
            'created_by' => null,
        ]);
        Mail::assertQueued(OrderStatusChangedMail::class);
    }

    #[Test]
    public function a_partial_dashboard_refund_leaves_status_unchanged(): void
    {
        Mail::fake();

        $order = Order::factory()->create([
            'status' => 'processing',
            'total' => 58.00,
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);

        $response = $this->postEvent($this->chargeRefundedEvent('pi_test_123', 2000));

        $response->assertOk();
        $order->refresh();
        $this->assertEquals('processing', $order->status);
        $this->assertEquals('20.00', $order->refunded_amount);
        Mail::assertNothingQueued();
    }

    #[Test]
    public function a_refund_we_already_recorded_is_a_noop(): void
    {
        Mail::fake();

        // Mirrors an order already fully refunded via the admin card.
        $order = Order::factory()->create([
            'status' => 'refunded',
            'total' => 58.00,
            'refunded_amount' => 58.00,
            'stripe_payment_intent_id' => 'pi_test_123',
            'stripe_refund_id' => 're_original',
        ]);

        $response = $this->postEvent($this->chargeRefundedEvent('pi_test_123', 5800, 're_test_1'));

        $response->assertOk();
        $order->refresh();
        // Untouched — no duplicate history row, no second email.
        $this->assertEquals('re_original', $order->stripe_refund_id);
        $this->assertDatabaseCount('order_status_history', 0);
        Mail::assertNothingQueued();
    }

    #[Test]
    public function a_refund_for_an_unknown_order_is_acknowledged_without_error(): void
    {
        $response = $this->postEvent($this->chargeRefundedEvent('pi_does_not_exist', 5800));

        $response->assertOk();
        $this->assertDatabaseCount('order_status_history', 0);
    }

    #[Test]
    public function a_successful_payment_with_no_order_is_acknowledged(): void
    {
        $response = $this->postEvent([
            'id' => 'evt_test_2',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_orphan', 'amount_received' => 5800]],
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function an_invalid_signature_is_rejected(): void
    {
        $response = $this->postEvent(
            $this->chargeRefundedEvent('pi_test_123', 5800),
            signature: 't='.time().',v1=deadbeef'
        );

        $response->assertStatus(400);
    }

    #[Test]
    public function a_missing_webhook_secret_is_rejected(): void
    {
        config()->set('services.stripe.webhook_secret', null);

        $response = $this->postEvent($this->chargeRefundedEvent('pi_test_123', 5800));

        $response->assertStatus(400);
    }
}
