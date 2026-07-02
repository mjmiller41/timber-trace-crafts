<?php

namespace Tests\Unit;

use App\Jobs\UpdateEtsyOrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateEtsyOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_throws_for_an_unrecognized_action(): void
    {
        $order = Order::factory()->create(['etsy_receipt_id' => '33333']);

        $this->expectException(\InvalidArgumentException::class);

        (new UpdateEtsyOrderStatus($order->etsy_receipt_id, 'bogus'))->handle();
    }
}
