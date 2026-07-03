<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EtsyLinkCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('etsy.shop_id', '12345678');
        Setting::set('etsy.access_token', Crypt::encryptString('some-token'));
        Setting::set('etsy.token_expires_at', now()->addHour()->toISOString());
    }

    public function test_link_copies_listing_metadata_including_readiness_state(): void
    {
        $product = Product::factory()->create(['etsy_listing_id' => null]);

        Http::fake([
            'api.etsy.com/v3/application/listings/999111/inventory' => Http::response([
                'products' => [
                    ['sku' => 'ABC-S', 'offerings' => [['quantity' => 3, 'readiness_state_id' => 1478211423469]]],
                ],
            ]),
            'api.etsy.com/v3/application/listings/999111' => Http::response([
                'listing_id' => 999111,
                'title' => 'Linked Title',
                'description' => 'Linked description',
                'state' => 'active',
                'taxonomy_id' => 1208,
                'shipping_profile_id' => 555,
            ]),
        ]);

        $this->artisan('etsy:link', ['product_id' => $product->id, 'etsy_listing_id' => '999111'])
            ->assertExitCode(0);

        $product->refresh();
        $this->assertEquals('999111', $product->etsy_listing_id);
        $this->assertEquals('Linked Title', $product->name);
        $this->assertEquals(1208, $product->etsy_taxonomy_id);
        $this->assertEquals(1478211423469, $product->etsy_readiness_state_id);
    }

    public function test_link_leaves_readiness_state_null_when_inventory_has_none(): void
    {
        $product = Product::factory()->create(['etsy_listing_id' => null]);

        Http::fake([
            'api.etsy.com/v3/application/listings/999222/inventory' => Http::response([
                'products' => [
                    ['sku' => 'ABC-S', 'offerings' => [['quantity' => 3]]],
                ],
            ]),
            'api.etsy.com/v3/application/listings/999222' => Http::response([
                'listing_id' => 999222,
                'title' => 'No Readiness Listing',
                'state' => 'active',
            ]),
        ]);

        $this->artisan('etsy:link', ['product_id' => $product->id, 'etsy_listing_id' => '999222'])
            ->assertExitCode(0);

        $this->assertNull($product->refresh()->etsy_readiness_state_id);
    }
}
