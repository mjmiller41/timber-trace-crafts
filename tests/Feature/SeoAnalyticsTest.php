<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeoAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Route the analytics channel to an isolated file so we can assert the
        // funnel events that App\Support\Analytics writes.
        $this->logPath = storage_path('logs/analytics-test.log');
        @unlink($this->logPath);

        config(['logging.channels.analytics' => [
            'driver' => 'single',
            'path' => $this->logPath,
            'level' => 'info',
        ]]);
    }

    protected function tearDown(): void
    {
        @unlink($this->logPath);
        parent::tearDown();
    }

    private function analyticsLog(): string
    {
        return file_exists($this->logPath) ? file_get_contents($this->logPath) : '';
    }

    // ── SEO fundamentals ────────────────────────────────────────────────────

    #[Test]
    public function sitemap_returns_valid_xml_including_active_products(): void
    {
        $product = Product::factory()->create(['status' => 'active']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('<urlset', false);
        $response->assertSee(route('product.show', $product->slug), false);
    }

    #[Test]
    public function product_page_emits_product_and_breadcrumb_schema(): void
    {
        $product = Product::factory()->create(['status' => 'active', 'price' => 42.00]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_qty' => 5]);

        $response = $this->get(route('product.show', $product->slug));

        $response->assertOk();
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
        // json_encode escapes forward slashes.
        $response->assertSee('"availability":"https:\/\/schema.org\/InStock"', false);
    }

    #[Test]
    public function robots_txt_references_the_sitemap(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap:', $robots);
        $this->assertStringContainsString('sitemap.xml', $robots);
    }

    // ── Analytics: client tracker gating ────────────────────────────────────

    #[Test]
    public function umami_tracker_is_absent_when_unconfigured(): void
    {
        config(['analytics.umami.website_id' => null]);

        // The cart page renders the full layout without depending on R2 media.
        $this->get(route('cart.index'))->assertOk()->assertDontSee('data-website-id', false);
    }

    #[Test]
    public function umami_tracker_renders_when_configured(): void
    {
        config([
            'analytics.umami.website_id' => 'test-website-id',
            'analytics.umami.script_url' => 'https://cloud.umami.is/script.js',
        ]);

        $response = $this->get(route('cart.index'));

        $response->assertOk();
        $response->assertSee('data-website-id="test-website-id"', false);
        $response->assertSee('https://cloud.umami.is/script.js', false);
    }

    // ── Analytics: server-side funnel events ────────────────────────────────

    #[Test]
    public function adding_to_cart_records_a_server_side_funnel_event(): void
    {
        $product = Product::factory()->create(['status' => 'active', 'price' => 25.00]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => null]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'qty' => 2,
        ]);

        $log = $this->analyticsLog();
        $this->assertStringContainsString('funnel.add_to_cart', $log);
        $this->assertStringContainsString('"product_id":'.$product->id, $log);
        $this->assertStringContainsString('"value":50', $log);
    }

    #[Test]
    public function viewing_confirmation_records_purchase_once_per_session(): void
    {
        $order = Order::factory()->create([
            'user_id' => null,
            'guest_email' => 'guest@example.com',
            'total' => 58.00,
        ]);

        $session = $this->withSession(['confirmed_orders' => [$order->id]]);
        $session->get(route('checkout.confirmation', ['order' => $order->id]))->assertOk();
        // A refresh must not double-count the conversion.
        $this->withSession([
            'confirmed_orders' => [$order->id],
            'tracked_purchases' => [$order->id],
        ])->get(route('checkout.confirmation', ['order' => $order->id]))->assertOk();

        $occurrences = substr_count($this->analyticsLog(), 'funnel.purchase');
        $this->assertSame(1, $occurrences, 'purchase should be recorded exactly once per order');
    }

    #[Test]
    public function server_events_can_be_disabled_via_config(): void
    {
        config(['analytics.server_events.enabled' => false]);

        $product = Product::factory()->create(['status' => 'active']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'qty' => 1,
        ]);

        $this->assertStringNotContainsString('funnel.add_to_cart', $this->analyticsLog());
    }
}
