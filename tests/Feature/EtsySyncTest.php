<?php

namespace Tests\Feature;

use App\Exceptions\EtsyApiException;
use App\Jobs\SyncProductToEtsy;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Shipment;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyInventorySync;
use App\Services\Etsy\EtsyOrderSync;
use App\Services\Etsy\EtsyProductSync;
use App\Services\Etsy\EtsyShipmentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class EtsySyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('etsy.shop_id', '12345678');
        Setting::set('etsy.access_token', 'fake-token');
        Setting::set('etsy.readiness_state_id', '1');
    }

    // ── Product Sync ──────────────────────────────────────────────────

    public function test_sync_product_creates_new_listing_and_saves_id(): void
    {
        Setting::set('etsy.taxonomy_id', '1208');
        $product = Product::factory()->create(['status' => 'active', 'etsy_listing_id' => null]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 5]);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('post')
            ->once()
            ->with('/application/shops/12345678/listings', Mockery::type('array'))
            ->andReturn(['listing_id' => 987654321]);

        $sync = new EtsyProductSync($client);
        $sync->syncProduct($product);

        $this->assertEquals('987654321', $product->fresh()->etsy_listing_id);
    }

    public function test_sync_product_does_not_re_dispatch_a_sync_job_via_the_observer(): void
    {
        Queue::fake();

        Setting::set('etsy.taxonomy_id', '1208');
        $product = Product::factory()->create([
            'status' => 'active',
            'etsy_listing_id' => null,
            'sold_on_etsy' => true,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 5]);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('post')
            ->once()
            ->with('/application/shops/12345678/listings', Mockery::type('array'))
            ->andReturn(['listing_id' => 987654321]);

        $sync = new EtsyProductSync($client);
        $sync->syncProduct($product);

        // The observer's own dispatch (from ::create() above) is expected;
        // syncProduct()'s internal updateQuietly() must not add a second one.
        Queue::assertPushed(SyncProductToEtsy::class, 1);
    }

    public function test_sync_product_updates_existing_listing(): void
    {
        Setting::set('etsy.shop_id', '12345678');
        $product = Product::factory()->create(['status' => 'active', 'etsy_listing_id' => '111222333']);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('patch')
            ->once()
            ->with('/application/shops/12345678/listings/111222333', Mockery::type('array'))
            ->andReturn(['listing_id' => 111222333]);
        $client->shouldReceive('put')
            ->once()
            ->with('/application/listings/111222333/inventory', Mockery::type('array'))
            ->andReturn([]);

        $sync = new EtsyProductSync($client);
        $sync->syncProduct($product);

        $this->assertEquals('111222333', $product->fresh()->etsy_listing_id);
    }

    public function test_sync_all_returns_result_counts(): void
    {
        Setting::set('etsy.taxonomy_id', '1208');
        Product::factory()->count(2)->create(['status' => 'active', 'etsy_listing_id' => null]);
        Product::factory()->create(['status' => 'active', 'etsy_listing_id' => '555']);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('post')->twice()->andReturnValues([['listing_id' => 99], ['listing_id' => 100]]);
        $client->shouldReceive('patch')->once()->andReturn(['listing_id' => 555]);
        $client->shouldReceive('put')->once()->andReturn([]);

        $sync = new EtsyProductSync($client);
        $result = $sync->syncAll();

        $this->assertEquals(2, $result->created);
        $this->assertEquals(1, $result->updated);
        $this->assertEquals(0, $result->failed);
    }

    // ── Inventory Sync ────────────────────────────────────────────────

    public function test_inventory_sync_skips_products_without_listing_id(): void
    {
        $product = Product::factory()->create(['etsy_listing_id' => null]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 5]);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldNotReceive('put');

        $sync = new EtsyInventorySync($client);
        $sync->syncProduct($product);
    }

    public function test_inventory_sync_puts_full_products_array(): void
    {
        $product = Product::factory()->create(['etsy_listing_id' => '777888']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'label' => 'Small', 'stock_qty' => 3, 'sku' => 'ABC-S']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'label' => 'Large', 'stock_qty' => 7, 'sku' => 'ABC-L']);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('put')
            ->once()
            ->with('/application/listings/777888/inventory', Mockery::on(function ($payload) {
                return isset($payload['products']) && count($payload['products']) === 2;
            }))
            ->andReturn([]);

        $sync = new EtsyInventorySync($client);
        $sync->syncProduct($product);
    }

    // ── Order Sync ────────────────────────────────────────────────────

    public function test_order_sync_imports_new_receipt_as_order(): void
    {
        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('get')
            ->with('/application/shops/12345678/receipts', Mockery::type('array'))
            ->andReturn([
                'results' => [
                    [
                        'receipt_id' => 9876543,
                        'name' => 'Jane Smith',
                        'first_line' => '123 Oak St',
                        'second_line' => null,
                        'city' => 'Portland',
                        'state' => 'OR',
                        'zip' => '97201',
                        'country_iso' => 'US',
                        'buyer_email' => 'jane@example.com',
                        'grandtotal' => ['amount' => 4500, 'divisor' => 100],
                        'subtotal' => ['amount' => 3500, 'divisor' => 100],
                        'total_shipping_cost' => ['amount' => 800, 'divisor' => 100],
                        'total_tax_cost' => ['amount' => 200, 'divisor' => 100],
                        'transactions' => [
                            [
                                'transaction_id' => 445566,
                                'title' => 'Oak Shelf',
                                'quantity' => 1,
                                'price' => ['amount' => 3500, 'divisor' => 100],
                                'sku' => 'SHELF-OAK',
                            ],
                        ],
                    ],
                ],
                'count' => 1,
            ]);

        $sync = new EtsyOrderSync($client);
        $sync->sync();

        $this->assertDatabaseHas('orders', [
            'etsy_receipt_id' => '9876543',
            'status' => 'processing',
            'guest_email' => 'jane@example.com',
        ]);

        $this->assertDatabaseHas('order_items', [
            'name_snapshot' => 'Oak Shelf',
            'qty' => 1,
            'etsy_transaction_id' => '445566',
        ]);
    }

    public function test_order_sync_skips_already_imported_receipts(): void
    {
        $order = Order::factory()->create(['etsy_receipt_id' => '9876543']);
        $order->items()->create([
            'name_snapshot' => 'Oak Shelf',
            'sku_snapshot' => 'SHELF-OAK',
            'price_snapshot' => 35.00,
            'qty' => 1,
            'subtotal' => 35.00,
        ]);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('get')
            ->andReturn([
                'results' => [
                    ['receipt_id' => 9876543, 'transactions' => []],
                ],
                'count' => 1,
            ]);

        $sync = new EtsyOrderSync($client);
        $result = $sync->sync();

        $this->assertEquals(1, $result->skipped);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_order_sync_heals_a_previously_itemless_order(): void
    {
        // A prior run failed mid-import and left an order with no items.
        Order::factory()->create(['etsy_receipt_id' => '9876543']);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('get')
            ->andReturn([
                'results' => [
                    [
                        'receipt_id' => 9876543,
                        'name' => 'Jane Doe',
                        'first_line' => '1 Main St',
                        'city' => 'Portland',
                        'state' => 'OR',
                        'zip' => '97201',
                        'country_iso' => 'US',
                        'buyer_email' => 'jane@example.com',
                        'grandtotal' => ['amount' => 3500, 'divisor' => 100],
                        'subtotal' => ['amount' => 3500, 'divisor' => 100],
                        'total_shipping_cost' => ['amount' => 0, 'divisor' => 100],
                        'total_tax_cost' => ['amount' => 0, 'divisor' => 100],
                        'transactions' => [
                            [
                                'title' => 'Oak Shelf',
                                'quantity' => 1,
                                'price' => ['amount' => 3500, 'divisor' => 100],
                                'sku' => 'SHELF-OAK',
                            ],
                        ],
                    ],
                ],
                'count' => 1,
            ]);

        $sync = new EtsyOrderSync($client);
        $result = $sync->sync();

        $this->assertEquals(1, $result->created);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('order_items', [
            'name_snapshot' => 'Oak Shelf',
            'qty' => 1,
        ]);
    }

    public function test_order_sync_does_not_advance_watermark_on_fetch_failure(): void
    {
        Setting::set('etsy.orders_last_synced_at', '2026-01-01T00:00:00.000000Z');

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('get')
            ->andThrow(new EtsyApiException('Etsy API error 500', 500));

        $sync = new EtsyOrderSync($client);
        $result = $sync->sync();

        $this->assertEquals(1, $result->failed);
        $this->assertEquals('2026-01-01T00:00:00.000000Z', Setting::get('etsy.orders_last_synced_at'));
    }

    // ── Shipment Sync ─────────────────────────────────────────────────

    public function test_shipment_push_sends_tracking_to_etsy(): void
    {
        $order = Order::factory()->create(['etsy_receipt_id' => '5551234']);
        $shipment = new Shipment([
            'carrier' => 'USPS',
            'tracking_number' => 'TRACK123',
        ]);
        $shipment->order_id = $order->id;

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('post')
            ->once()
            ->with(
                '/application/shops/12345678/receipts/5551234/tracking',
                Mockery::on(fn ($p) => $p['carrier_name'] === 'USPS' && $p['tracking_code'] === 'TRACK123')
            )
            ->andReturn([]);

        $sync = new EtsyShipmentSync($client);
        $result = $sync->pushShipment($order, $shipment);

        $this->assertTrue($result);
    }

    public function test_shipment_push_skips_non_etsy_orders(): void
    {
        $order = Order::factory()->create(['etsy_receipt_id' => null]);
        $shipment = new Shipment(['carrier' => 'UPS', 'tracking_number' => 'XYZ']);
        $shipment->order_id = $order->id;

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldNotReceive('post');

        $sync = new EtsyShipmentSync($client);
        $result = $sync->pushShipment($order, $shipment);

        $this->assertFalse($result);
    }
}
