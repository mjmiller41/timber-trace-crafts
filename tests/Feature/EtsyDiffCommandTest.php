<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EtsyDiffCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('etsy.shop_id', '12345678');
        Setting::set('etsy.access_token', Crypt::encryptString('some-token'));
        Setting::set('etsy.token_expires_at', now()->addHour()->toISOString());
    }

    public function test_html_encoded_etsy_title_matches_decoded_db_name(): void
    {
        Product::factory()->create([
            'etsy_listing_id' => '555',
            'name' => "Valentine's Gift Box",
            'price' => 40.00,
        ]);

        Http::fake([
            'api.etsy.com/v3/application/shops/12345678/listings*' => Http::response([
                'results' => [[
                    'listing_id' => 555,
                    'title' => 'Valentine&#39;s Gift Box',
                    'state' => 'active',
                    'price' => ['amount' => 4000, 'divisor' => 100],
                    'tags' => [],
                    'shop_section_id' => null,
                ]],
            ]),
        ]);

        $this->artisan('etsy:diff', ['--type' => 'listings'])
            ->doesntExpectOutputToContain('Conflicts:')
            ->assertExitCode(0);
    }
}
