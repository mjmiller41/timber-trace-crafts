<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexNowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_submits_active_product_urls_to_indexnow(): void
    {
        Http::fake([
            'api.indexnow.org/*' => Http::response('', 200),
        ]);

        $product = Product::factory()->create(['status' => 'active']);
        Product::factory()->create(['status' => 'draft']);

        $this->artisan('seo:indexnow')->assertSuccessful();

        Http::assertSent(function ($request) use ($product) {
            return str_contains($request->url(), 'api.indexnow.org')
                && $request['key'] === config('services.indexnow.key')
                && in_array(route('product.show', $product->slug), $request['urlList'], true);
        });
    }

    #[Test]
    public function dry_run_does_not_call_the_api(): void
    {
        Http::fake();

        $this->artisan('seo:indexnow --dry-run')->assertSuccessful();

        Http::assertNothingSent();
    }
}
