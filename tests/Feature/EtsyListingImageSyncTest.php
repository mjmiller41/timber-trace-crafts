<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Setting;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyListingImageSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class EtsyListingImageSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('etsy.shop_id', '12345678');
        Storage::fake(config('filesystems.default'));
    }

    private function attachMedia(Product $product, array $overrides = []): ProductMedia
    {
        $media = Media::factory()->create();
        Storage::disk($media->disk)->put($media->path, 'fake-image-bytes');

        return ProductMedia::create(array_merge([
            'product_id' => $product->id,
            'media_id' => $media->id,
            'sort_order' => 0,
            'is_primary' => false,
        ], $overrides));
    }

    public function test_uploads_untracked_images_in_order_and_stores_listing_image_ids(): void
    {
        $product = Product::factory()->create(['etsy_listing_id' => '444555']);
        $second = $this->attachMedia($product, ['sort_order' => 2]);
        $primary = $this->attachMedia($product, ['is_primary' => true, 'sort_order' => 5]);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/application/listings/444555/images')
            ->andReturn(['results' => []]);
        $client->shouldReceive('postFile')
            ->twice()
            ->with(
                '/application/shops/12345678/listings/444555/images',
                Mockery::type('array'),
                'image',
                'fake-image-bytes',
                Mockery::type('string')
            )
            ->andReturnValues([['listing_image_id' => 91], ['listing_image_id' => 92]]);

        $result = (new EtsyListingImageSync($client))->syncProduct($product);

        $this->assertEquals(2, $result->created);
        $this->assertEquals(0, $result->failed);
        // Primary image uploads first despite higher sort_order
        $this->assertEquals('91', $primary->fresh()->etsy_listing_image_id);
        $this->assertEquals('92', $second->fresh()->etsy_listing_image_id);
    }

    public function test_skips_product_when_listing_has_images_we_did_not_upload(): void
    {
        $product = Product::factory()->create(['etsy_listing_id' => '444555']);
        $this->attachMedia($product);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/application/listings/444555/images')
            ->andReturn(['results' => [['listing_image_id' => 777]]]);
        $client->shouldNotReceive('postFile');

        $result = (new EtsyListingImageSync($client))->syncProduct($product);

        $this->assertEquals(1, $result->skipped);
        $this->assertEquals(0, $result->created);
    }

    public function test_force_uploads_even_when_listing_has_untracked_images(): void
    {
        $product = Product::factory()->create(['etsy_listing_id' => '444555']);
        $this->attachMedia($product);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldNotReceive('get');
        $client->shouldReceive('postFile')->once()->andReturn(['listing_image_id' => 93]);

        $result = (new EtsyListingImageSync($client))->syncProduct($product, force: true);

        $this->assertEquals(1, $result->created);
    }

    public function test_already_tracked_images_are_not_reuploaded(): void
    {
        $product = Product::factory()->create(['etsy_listing_id' => '444555']);
        $this->attachMedia($product, ['etsy_listing_image_id' => '91']);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldNotReceive('postFile');

        $result = (new EtsyListingImageSync($client))->syncProduct($product);

        $this->assertEquals(1, $result->skipped);
    }

    public function test_unsupported_mime_type_counts_as_failed_without_upload(): void
    {
        $product = Product::factory()->create(['etsy_listing_id' => '444555']);
        $media = Media::factory()->create(['mime_type' => 'image/webp']);
        ProductMedia::create([
            'product_id' => $product->id,
            'media_id' => $media->id,
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldReceive('get')->once()->andReturn(['results' => []]);
        $client->shouldNotReceive('postFile');

        $result = (new EtsyListingImageSync($client))->syncProduct($product);

        $this->assertEquals(1, $result->failed);
        $this->assertEquals(0, $result->created);
    }

    public function test_product_without_listing_id_is_skipped(): void
    {
        $product = Product::factory()->create(['etsy_listing_id' => null]);

        $client = Mockery::mock(EtsyClient::class);
        $client->shouldNotReceive('get', 'postFile');

        $result = (new EtsyListingImageSync($client))->syncProduct($product);

        $this->assertEquals(1, $result->skipped);
    }
}
