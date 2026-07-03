<?php

namespace Tests\Feature\Admin;

use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Stripe\Exception\InvalidRequestException;
use Stripe\Refund;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    #[Test]
    public function admin_can_update_order_status(): void
    {
        Mail::fake();

        $order = Order::factory()->create(['status' => 'processing', 'user_id' => User::factory()->create()->id]);

        $response = $this->actingAs($this->admin())->patch(route('admin.orders.status', $order), [
            'status' => 'shipped',
        ]);

        $response->assertRedirect();
        $this->assertEquals('shipped', $order->fresh()->status);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'status' => 'shipped',
        ]);
        Mail::assertQueued(OrderStatusChangedMail::class);
    }

    #[Test]
    public function non_admin_cannot_update_order_status(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['status' => 'processing']);

        $response = $this->actingAs($customer)->patch(route('admin.orders.status', $order), [
            'status' => 'shipped',
        ]);

        $response->assertForbidden();
        $this->assertEquals('processing', $order->fresh()->status);
    }

    #[Test]
    public function admin_can_add_a_shipment(): void
    {
        $order = Order::factory()->create(['status' => 'processing']);

        $response = $this->actingAs($this->admin())->post(route('admin.orders.shipment', $order), [
            'carrier' => 'USPS',
            'tracking_number' => 'TRACK123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'tracking_number' => 'TRACK123',
        ]);
        $this->assertEquals('shipped', $order->fresh()->status);
    }

    #[Test]
    public function non_admin_cannot_add_a_shipment(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['status' => 'processing']);

        $response = $this->actingAs($customer)->post(route('admin.orders.shipment', $order), [
            'carrier' => 'USPS',
            'tracking_number' => 'TRACK123',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('shipments', ['order_id' => $order->id]);
    }

    private function mockRefund(string $refundId = 're_test_123'): void
    {
        $this->mock(StripeService::class)
            ->shouldReceive('refundPayment')
            ->andReturn(Refund::constructFrom(['id' => $refundId, 'status' => 'succeeded']));
    }

    #[Test]
    public function admin_can_fully_refund_a_stripe_order(): void
    {
        Mail::fake();
        $this->mockRefund();

        $order = Order::factory()->create([
            'status' => 'processing',
            'total' => 58.00,
            'stripe_payment_intent_id' => 'pi_test_123',
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.orders.refund', $order));

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('refunded', $order->status);
        $this->assertEquals('58.00', $order->refunded_amount);
        $this->assertEquals('re_test_123', $order->stripe_refund_id);
        $this->assertNotNull($order->refunded_at);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'status' => 'refunded',
        ]);
        Mail::assertQueued(OrderStatusChangedMail::class);
    }

    #[Test]
    public function admin_can_partially_refund_a_stripe_order(): void
    {
        Mail::fake();
        $this->mockRefund();

        $order = Order::factory()->create([
            'status' => 'processing',
            'total' => 58.00,
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.orders.refund', $order), [
            'amount' => 20.00,
        ]);

        $response->assertRedirect();
        $order->refresh();
        // Partial refund leaves status untouched and a balance remaining.
        $this->assertEquals('processing', $order->status);
        $this->assertEquals('20.00', $order->refunded_amount);
        $this->assertEquals(38.00, $order->refundableAmount());
        Mail::assertNothingQueued();
    }

    #[Test]
    public function refund_amount_cannot_exceed_remaining_balance(): void
    {
        $order = Order::factory()->create([
            'total' => 58.00,
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.orders.refund', $order), [
            'amount' => 100.00,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertNull($order->fresh()->refunded_amount);
    }

    #[Test]
    public function cannot_refund_an_order_without_a_stripe_payment(): void
    {
        $order = Order::factory()->create([
            'total' => 58.00,
            'stripe_payment_intent_id' => null,
            'etsy_receipt_id' => 'etsy_999',
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.orders.refund', $order));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNull($order->fresh()->refunded_amount);
    }

    #[Test]
    public function stripe_failure_leaves_the_order_unchanged(): void
    {
        $this->mock(StripeService::class)
            ->shouldReceive('refundPayment')
            ->andThrow(new InvalidRequestException('Charge already refunded'));

        $order = Order::factory()->create([
            'status' => 'processing',
            'total' => 58.00,
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.orders.refund', $order));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $order->refresh();
        $this->assertEquals('processing', $order->status);
        $this->assertNull($order->refunded_amount);
        $this->assertNull($order->stripe_refund_id);
    }

    #[Test]
    public function refunded_status_from_the_dropdown_is_blocked_for_stripe_orders(): void
    {
        Mail::fake();

        $order = Order::factory()->create([
            'status' => 'processing',
            'total' => 58.00,
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);

        $response = $this->actingAs($this->admin())->patch(route('admin.orders.status', $order), [
            'status' => 'refunded',
        ]);

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('error');
        $this->assertEquals('processing', $order->fresh()->status);
        $this->assertDatabaseMissing('order_status_history', [
            'order_id' => $order->id,
            'status' => 'refunded',
        ]);
        Mail::assertNothingQueued();
    }

    #[Test]
    public function refunded_status_from_the_dropdown_is_allowed_for_non_stripe_orders(): void
    {
        Mail::fake();

        $order = Order::factory()->create([
            'status' => 'processing',
            'total' => 58.00,
            'stripe_payment_intent_id' => null,
            'etsy_receipt_id' => 'etsy_999',
        ]);

        $response = $this->actingAs($this->admin())->patch(route('admin.orders.status', $order), [
            'status' => 'refunded',
        ]);

        $response->assertRedirect();
        $this->assertEquals('refunded', $order->fresh()->status);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'status' => 'refunded',
        ]);
    }

    #[Test]
    public function non_admin_cannot_refund_an_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'total' => 58.00,
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);

        $response = $this->actingAs($customer)->post(route('admin.orders.refund', $order));

        $response->assertForbidden();
        $this->assertNull($order->fresh()->refunded_amount);
    }
}
