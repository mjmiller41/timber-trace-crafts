<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EtsyWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    // Raw signing secret (before whsec_ encoding) used across all tests
    private string $rawSecret;

    private string $whsecSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rawSecret = random_bytes(32);
        $this->whsecSecret = 'whsec_'.base64_encode($this->rawSecret);

        Config::set('services.etsy.webhook_secret', $this->whsecSecret);
    }

    /** Build a valid signed POST to /webhooks/etsy */
    private function signedPost(array $payload, int $timestampOffset = 0): array
    {
        $body = json_encode($payload);
        $webhookId = 'msg_'.bin2hex(random_bytes(8));
        $timestamp = (string) (time() + $timestampOffset);
        $signedContent = "{$webhookId}.{$timestamp}.{$body}";
        $sig = base64_encode(hash_hmac('sha256', $signedContent, $this->rawSecret, true));

        return [
            'body' => $body,
            'headers' => [
                'webhook-id' => $webhookId,
                'webhook-timestamp' => $timestamp,
                'webhook-signature' => $sig,
            ],
        ];
    }

    private function postWebhook(array $payload, array $headerOverrides = [], int $timestampOffset = 0)
    {
        ['body' => $body, 'headers' => $headers] = $this->signedPost($payload, $timestampOffset);

        return $this->call(
            'POST',
            '/webhooks/etsy',
            [],
            [],
            [],
            array_merge(
                ['CONTENT_TYPE' => 'application/json'],
                collect($headers)->mapWithKeys(fn ($v, $k) => ['HTTP_'.strtoupper(str_replace('-', '_', $k)) => $v])->toArray(),
                collect($headerOverrides)->mapWithKeys(fn ($v, $k) => ['HTTP_'.strtoupper(str_replace('-', '_', $k)) => $v])->toArray(),
            ),
            $body
        );
    }

    #[Test]
    public function it_rejects_requests_with_no_signature_headers(): void
    {
        $response = $this->postJson('/webhooks/etsy', ['event_type' => 'order.paid']);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_rejects_requests_with_wrong_signature(): void
    {
        $response = $this->postWebhook(
            ['event_type' => 'order.paid'],
            ['webhook-signature' => base64_encode('bad-signature')]
        );

        $response->assertStatus(401);
    }

    #[Test]
    public function it_rejects_when_webhook_secret_is_not_configured(): void
    {
        Config::set('services.etsy.webhook_secret', null);

        $response = $this->postWebhook(['event_type' => 'order.paid']);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_rejects_stale_timestamps(): void
    {
        $response = $this->postWebhook(['event_type' => 'order.paid'], [], -400);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_accepts_valid_signed_requests_and_returns_ok(): void
    {
        $response = $this->postWebhook(['event_type' => 'order.delivered', 'resource_url' => 'https://api.etsy.com/v3/application/shops/1/receipts/999']);

        $response->assertOk()->assertJson(['ok' => true]);
    }

    #[Test]
    public function it_handles_unknown_event_types_without_error(): void
    {
        $response = $this->postWebhook(['event_type' => 'some.future.event', 'resource_url' => '']);

        $response->assertOk()->assertJson(['ok' => true]);
    }

    #[Test]
    public function it_sends_admin_email_on_order_paid_for_existing_order(): void
    {
        Mail::fake();

        $order = \App\Models\Order::factory()->create([
            'etsy_receipt_id' => '12345',
            'etsy_is_paid' => false,
            'status' => 'pending_payment',
        ]);

        $this->postWebhook([
            'event_type' => 'order.paid',
            'resource_url' => 'https://api.etsy.com/v3/application/shops/1/receipts/12345',
        ]);

        Mail::assertSent(\App\Mail\EtsyNewOrderMail::class, fn ($mail) => $mail->order->id === $order->id);

        $order->refresh();
        $this->assertEquals('processing', $order->status);
        $this->assertTrue((bool) $order->etsy_is_paid);
    }

    #[Test]
    public function it_updates_order_status_on_shipped(): void
    {
        $order = \App\Models\Order::factory()->create([
            'etsy_receipt_id' => '99999',
            'status' => 'processing',
        ]);

        $this->postWebhook([
            'event_type' => 'order.shipped',
            'resource_url' => 'https://api.etsy.com/v3/application/shops/1/receipts/99999',
        ]);

        $order->refresh();
        $this->assertEquals('shipped', $order->status);
        $this->assertTrue((bool) $order->etsy_is_shipped);
    }

    #[Test]
    public function it_updates_order_status_on_canceled(): void
    {
        $order = \App\Models\Order::factory()->create([
            'etsy_receipt_id' => '77777',
            'status' => 'processing',
        ]);

        $this->postWebhook([
            'event_type' => 'order.canceled',
            'resource_url' => 'https://api.etsy.com/v3/application/shops/1/receipts/77777',
        ]);

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    #[Test]
    public function it_updates_order_status_on_delivered(): void
    {
        $order = \App\Models\Order::factory()->create([
            'etsy_receipt_id' => '55555',
            'status' => 'shipped',
        ]);

        $this->postWebhook([
            'event_type' => 'order.delivered',
            'resource_url' => 'https://api.etsy.com/v3/application/shops/1/receipts/55555',
        ]);

        $order->refresh();
        $this->assertEquals('delivered', $order->status);
    }
}
