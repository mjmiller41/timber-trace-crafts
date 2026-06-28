<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\User;
use App\Services\Media\MediaUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    private function disk(): string
    {
        return config('filesystems.default');
    }

    #[Test]
    public function uploading_a_png_stores_the_original_and_a_webp_variant(): void
    {
        Storage::fake($this->disk());
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('photo.png', 64, 64),
        ]);

        $response->assertOk()->assertJsonStructure(['id', 'url', 'name']);

        $media = Media::firstOrFail();
        Storage::disk($this->disk())->assertExists($media->path);
        Storage::disk($this->disk())->assertExists(MediaUploader::webpPath($media->path));
        $this->assertStringEndsWith('.png', $media->path);
        $this->assertStringEndsWith('.webp', MediaUploader::webpPath($media->path));
    }

    #[Test]
    public function uploading_a_webp_creates_no_extra_sibling(): void
    {
        Storage::fake($this->disk());
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('already.webp', 64, 64),
        ])->assertOk();

        $media = Media::firstOrFail();
        Storage::disk($this->disk())->assertExists($media->path);
        // webpPath() returns null for a non jpg/png source, so no sibling is made.
        $this->assertNull(MediaUploader::webpPath($media->path));
        $this->assertCount(1, Storage::disk($this->disk())->allFiles());
    }

    #[Test]
    public function deleting_media_removes_the_original_and_its_webp_variant(): void
    {
        Storage::fake($this->disk());
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('gone.jpg', 64, 64),
        ])->assertOk();

        $media = Media::firstOrFail();
        $webp = MediaUploader::webpPath($media->path);
        Storage::disk($this->disk())->assertExists($media->path);
        Storage::disk($this->disk())->assertExists($webp);

        $this->actingAs($admin)->delete(route('admin.media.destroy', $media));

        Storage::disk($this->disk())->assertMissing($media->path);
        Storage::disk($this->disk())->assertMissing($webp);
        $this->assertModelMissing($media);
    }

    #[Test]
    public function the_library_json_endpoint_returns_media_for_the_picker(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $media = Media::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->getJson(route('admin.media.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'url', 'name', 'alt', 'is_image']],
                'current_page',
                'last_page',
            ])
            ->assertJsonCount(3, 'data');

        $this->assertContains($media->first()->id, collect($response->json('data'))->pluck('id'));
    }

    #[Test]
    public function the_library_json_endpoint_filters_by_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Media::factory()->create(['original_name' => 'walnut-earrings.png']);
        Media::factory()->create(['original_name' => 'maple-box.png']);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.media.index', ['search' => 'walnut']));

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertStringContainsString('walnut', $response->json('data.0.name'));
    }

    #[Test]
    public function the_proxy_streams_the_file_when_the_disk_can_read_it(): void
    {
        Storage::fake($this->disk());
        $admin = User::factory()->create(['role' => 'admin']);
        Storage::disk($this->disk())->put('media/there.webp', 'REAL-BYTES');
        $media = Media::factory()->create(['mime_type' => 'image/webp', 'path' => 'media/there.webp']);

        $response = $this->actingAs($admin)->get(route('admin.media.proxy', $media));

        $response->assertOk();
        $this->assertSame('REAL-BYTES', $response->streamedContent());
        $response->assertHeader('Content-Type', 'image/webp');
    }

    #[Test]
    public function the_proxy_falls_back_to_the_public_url_when_the_disk_cannot_stream(): void
    {
        Storage::fake($this->disk());
        Http::fake(['*' => Http::response('FALLBACK-BYTES', 200)]);
        $admin = User::factory()->create(['role' => 'admin']);
        // No underlying file on the faked disk → readStream yields null.
        $media = Media::factory()->create(['mime_type' => 'image/webp', 'path' => 'media/missing.webp']);

        $response = $this->actingAs($admin)->get(route('admin.media.proxy', $media));

        $response->assertOk();
        $this->assertSame('FALLBACK-BYTES', $response->getContent());
        Http::assertSentCount(1);
    }

    #[Test]
    public function a_non_admin_cannot_upload_media(): void
    {
        Storage::fake($this->disk());
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('nope.png'),
        ])->assertForbidden();

        $this->assertSame(0, Media::count());
    }
}
