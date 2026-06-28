<?php

namespace Tests\Feature;

use App\Jobs\ImportEtsyOrder;
use App\Mail\EtsyNewOrderMail;
use App\Services\Etsy\EtsyClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportEtsyOrderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_the_order_then_increments_counter_and_queues_admin_mail(): void
    {
        Mail::fake();

        $resourceUrl = 'https://api.etsy.com/v3/application/shops/1/receipts/9876543';

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/application/shops/1/receipts/9876543')
            ->andReturn([
                'receipt_id' => 9876543,
                'name' => 'Jane Smith',
                'buyer_email' => 'jane@example.com',
                'grandtotal' => ['amount' => 4500, 'divisor' => 100],
                'subtotal' => ['amount' => 3500, 'divisor' => 100],
                'total_shipping_cost' => ['amount' => 800, 'divisor' => 100],
                'total_tax_cost' => ['amount' => 200, 'divisor' => 100],
                'transactions' => [
                    ['title' => 'Oak Shelf', 'quantity' => 1, 'price' => ['amount' => 3500, 'divisor' => 100], 'sku' => 'SHELF-OAK'],
                ],
            ]);
        $this->app->instance(EtsyClient::class, $client);

        (new ImportEtsyOrder($resourceUrl))->handle();

        $this->assertDatabaseHas('orders', [
            'etsy_receipt_id' => '9876543',
            'status' => 'processing',
            'etsy_is_paid' => true,
        ]);

        $this->assertEquals(1, Cache::get('etsy.new_orders'));

        Mail::assertQueued(
            EtsyNewOrderMail::class,
            fn ($mail) => $mail->order->etsy_receipt_id === '9876543'
        );
    }

    #[Test]
    public function it_does_nothing_when_the_receipt_is_empty(): void
    {
        Mail::fake();

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('get')->once()->andReturn([]);
        $this->app->instance(EtsyClient::class, $client);

        (new ImportEtsyOrder('https://api.etsy.com/v3/application/shops/1/receipts/1'))->handle();

        $this->assertDatabaseCount('orders', 0);
        $this->assertNull(Cache::get('etsy.new_orders'));
        Mail::assertNothingQueued();
    }
}
