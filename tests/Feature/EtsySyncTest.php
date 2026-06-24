<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyInventorySync;
use App\Services\Etsy\EtsyOrderSync;
use App\Services\Etsy\EtsyProductSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    }

    // ── Product Sync ──────────────────────────────────────────────────

    public function test_sync_product_creates_new_listing_and_saves_id(): void
    {
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

    public function test_sync_product_updates_existing_listing(): void
    {
        $product = Product::factory()->create(['status' => 'active', 'etsy_listing_id' => '111222333']);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('put')
            ->once()
            ->with('/application/listings/111222333', Mockery::type('array'))
            ->andReturn(['listing_id' => 111222333]);

        $sync = new EtsyProductSync($client);
        $sync->syncProduct($product);

        $this->assertEquals('111222333', $product->fresh()->etsy_listing_id);
    }

    public function test_sync_all_returns_result_counts(): void
    {
        Product::factory()->count(2)->create(['status' => 'active', 'etsy_listing_id' => null]);
        Product::factory()->create(['status' => 'active', 'etsy_listing_id' => '555']);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('post')->twice()->andReturnValues([['listing_id' => 99], ['listing_id' => 100]]);
        $client->shouldReceive('put')->once()->andReturn(['listing_id' => 555]);

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
        ]);
    }

    public function test_order_sync_skips_already_imported_receipts(): void
    {
        Order::factory()->create(['etsy_receipt_id' => '9876543']);

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
}
