<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
