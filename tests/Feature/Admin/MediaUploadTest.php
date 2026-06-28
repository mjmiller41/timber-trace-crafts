<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\User;
use App\Services\Media\MediaUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
