<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EtsyDiffAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('etsy.shop_id', '12345678');
        Setting::set('etsy.access_token', Crypt::encryptString('some-token'));
        Setting::set('etsy.token_expires_at', now()->addHour()->toISOString());
        Setting::set('etsy.readiness_state_id', 1);
    }

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * @return array{0: Product, 1: array} the product and a cached diff report with one conflict
     */
    private function seedConflict(array $differences, int $variantCount = 0): array
    {
        $product = Product::factory()->create([
            'etsy_listing_id' => '555',
            'name' => 'Walnut Serving Board',
            'price' => 40.00,
            'sale_price' => null,
        ]);

        $report = [
            'generated_at' => now()->toISOString(),
            'etsyOnly' => [],
            'dbOnly' => [],
            'conflicts' => [[
                'listing_id' => '555',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'variant_count' => $variantCount,
                'differences' => $differences,
            ]],
            'matched' => 0,
        ];

        Cache::put('etsy.product_diff', $report, now()->addHour());

        return [$product, $report];
    }

    public function test_admin_can_run_a_product_diff_and_see_conflicts(): void
    {
        Product::factory()->create([
            'etsy_listing_id' => '555',
            'name' => 'Walnut Serving Board',
            'price' => 40.00,
            'sale_price' => null,
        ]);

        Http::fake([
            'api.etsy.com/v3/application/shops/12345678/listings*' => Http::response([
                'results' => [[
                    'listing_id' => 555,
                    'title' => 'Walnut Serving Board Deluxe',
                    'state' => 'active',
                    'price' => ['amount' => 5000, 'divisor' => 100],
                ]],
            ]),
        ]);

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.etsy.diff.products'));

        $response->assertRedirect(route('admin.etsy.index'));
        $response->assertSessionHas('success');

        $report = Cache::get('etsy.product_diff');
        $this->assertCount(1, $report['conflicts']);
        $this->assertSame(
            ['db' => 'Walnut Serving Board', 'etsy' => 'Walnut Serving Board Deluxe'],
            $report['conflicts'][0]['differences']['title']
        );
        $this->assertSame(['db' => 40.0, 'etsy' => 50.0], $report['conflicts'][0]['differences']['price']);

        $page = $this->actingAs($this->adminUser())->get(route('admin.etsy.index'));
        $page->assertOk();
        $page->assertSee('Walnut Serving Board Deluxe');
        $page->assertSee('1 conflict(s)');
    }

    public function test_diff_reports_matched_when_listing_and_product_agree(): void
    {
        Product::factory()->create([
            'etsy_listing_id' => '555',
            'name' => "Valentine's Gift Box",
            'price' => 40.00,
            'sale_price' => null,
        ]);

        Http::fake([
            'api.etsy.com/v3/application/shops/12345678/listings*' => Http::response([
                'results' => [[
                    'listing_id' => 555,
                    'title' => 'Valentine&#39;s Gift Box',
                    'state' => 'active',
                    'price' => ['amount' => 4000, 'divisor' => 100],
                ]],
            ]),
        ]);

        $this->actingAs($this->adminUser())->post(route('admin.etsy.diff.products'));

        $report = Cache::get('etsy.product_diff');
        $this->assertSame(1, $report['matched']);
        $this->assertCount(0, $report['conflicts']);
    }

    public function test_resolve_keep_etsy_updates_the_product_without_calling_etsy(): void
    {
        Queue::fake();
        Http::fake();

        [$product] = $this->seedConflict([
            'title' => ['db' => 'Walnut Serving Board', 'etsy' => 'Walnut Serving Board Deluxe'],
            'price' => ['db' => 40.0, 'etsy' => 50.0],
        ]);

        $response = $this->actingAs($this->adminUser())->post(route('admin.etsy.diff.resolve'), [
            'resolutions' => [
                $product->id => ['title' => 'etsy', 'price' => 'etsy'],
            ],
        ]);

        $response->assertRedirect(route('admin.etsy.index'));
        $response->assertSessionHas('success');

        $product->refresh();
        $this->assertSame('Walnut Serving Board Deluxe', $product->name);
        $this->assertSame(50.0, (float) $product->price);

        Http::assertNothingSent();
        Queue::assertNothingPushed();

        $this->assertCount(0, Cache::get('etsy.product_diff')['conflicts']);
    }

    public function test_resolve_keep_website_pushes_the_product_to_etsy(): void
    {
        Http::fake(['api.etsy.com/*' => Http::response(['results' => []])]);

        [$product] = $this->seedConflict([
            'price' => ['db' => 40.0, 'etsy' => 50.0],
        ]);

        $this->actingAs($this->adminUser())->post(route('admin.etsy.diff.resolve'), [
            'resolutions' => [
                $product->id => ['price' => 'db'],
            ],
        ]);

        Http::assertSent(fn (Request $request) => $request->method() === 'PATCH'
            && str_contains($request->url(), '/shops/12345678/listings/555'));

        $this->assertSame(40.0, (float) $product->refresh()->price);
        $this->assertCount(0, Cache::get('etsy.product_diff')['conflicts']);
    }

    public function test_mixed_resolution_applies_etsy_values_then_pushes_once(): void
    {
        Http::fake(['api.etsy.com/*' => Http::response(['results' => []])]);

        [$product] = $this->seedConflict([
            'title' => ['db' => 'Walnut Serving Board', 'etsy' => 'Walnut Serving Board Deluxe'],
            'price' => ['db' => 40.0, 'etsy' => 50.0],
        ]);

        $this->actingAs($this->adminUser())->post(route('admin.etsy.diff.resolve'), [
            'resolutions' => [
                $product->id => ['title' => 'etsy', 'price' => 'db'],
            ],
        ]);

        // Etsy title was merged into the DB first, then the product pushed once
        $this->assertSame('Walnut Serving Board Deluxe', $product->refresh()->name);

        Http::assertSentCount(2); // PATCH listing + PUT inventory — one push, no extras
        Http::assertSent(fn (Request $request) => $request->method() === 'PATCH'
            && str_contains($request->url(), '/listings/555'));
    }

    public function test_partial_resolution_keeps_remaining_conflict_fields(): void
    {
        Queue::fake();
        Http::fake();

        [$product] = $this->seedConflict([
            'title' => ['db' => 'Walnut Serving Board', 'etsy' => 'Walnut Serving Board Deluxe'],
            'price' => ['db' => 40.0, 'etsy' => 50.0],
        ]);

        $this->actingAs($this->adminUser())->post(route('admin.etsy.diff.resolve'), [
            'resolutions' => [
                $product->id => ['title' => 'etsy'],
            ],
        ]);

        $conflicts = Cache::get('etsy.product_diff')['conflicts'];
        $this->assertCount(1, $conflicts);
        $this->assertArrayHasKey('price', $conflicts[0]['differences']);
        $this->assertArrayNotHasKey('title', $conflicts[0]['differences']);
    }

    public function test_resolve_with_expired_report_shows_an_error(): void
    {
        $response = $this->actingAs($this->adminUser())->post(route('admin.etsy.diff.resolve'), [
            'resolutions' => [1 => ['price' => 'db']],
        ]);

        $response->assertRedirect(route('admin.etsy.index'));
        $response->assertSessionHas('error');
    }

    public function test_resolve_rejects_invalid_choice_values(): void
    {
        $this->seedConflict(['price' => ['db' => 40.0, 'etsy' => 50.0]]);

        $response = $this->actingAs($this->adminUser())->post(route('admin.etsy.diff.resolve'), [
            'resolutions' => [1 => ['price' => 'bogus']],
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_non_admin_cannot_access_diff_routes(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)->post(route('admin.etsy.diff.products'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.etsy.diff.resolve'))->assertForbidden();
    }
}
