<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function order_status_lookup_is_rate_limited(): void
    {
        $payload = [
            'order_number' => 999999,
            'email' => 'nobody@example.com',
        ];

        // throttle:5,1 — the first five attempts are allowed (here they 302
        // back with a "no order found" error), the sixth is blocked with 429.
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('order.status.lookup'), $payload)
                ->assertStatus(302);
        }

        $this->post(route('order.status.lookup'), $payload)
            ->assertStatus(429);
    }

    #[Test]
    public function a_valid_signed_link_shows_the_order_without_an_email_param(): void
    {
        $order = Order::factory()->create(['guest_email' => 'guest@example.com']);

        $url = URL::signedRoute('order.status.view', ['order' => $order->id]);

        $this->get($url)->assertOk();
    }

    #[Test]
    public function an_unsigned_or_tampered_link_is_rejected(): void
    {
        $order = Order::factory()->create(['guest_email' => 'guest@example.com']);

        // No signature at all.
        $this->get(route('order.status.view', ['order' => $order->id]))
            ->assertForbidden();

        // Tampered signature.
        $url = URL::signedRoute('order.status.view', ['order' => $order->id]).'tampered';
        $this->get($url)->assertForbidden();
    }

    #[Test]
    public function a_valid_temporary_signed_link_within_90_days_is_accepted(): void
    {
        $order = Order::factory()->create(['guest_email' => 'guest@example.com']);

        $url = URL::temporarySignedRoute('order.status.view', now()->addDays(89), ['order' => $order->id]);

        $this->get($url)->assertOk();
    }

    #[Test]
    public function an_expired_signed_link_is_rejected(): void
    {
        $order = Order::factory()->create(['guest_email' => 'guest@example.com']);

        $url = URL::temporarySignedRoute('order.status.view', now()->subDay(), ['order' => $order->id]);

        $this->get($url)->assertForbidden();
    }
}
