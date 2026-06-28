<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackfillMediaWebpCommandTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDisk(): Filesystem
    {
        return Storage::fake(config('filesystems.default'));
    }

    private function pngBytes(): string
    {
        return (string) (new ImageManager(GdDriver::class))
            ->createImage(4, 4)
            ->encode(new PngEncoder);
    }

    #[Test]
    public function it_generates_a_missing_webp_sibling_for_raster_media(): void
    {
        $disk = $this->fakeDisk();
        $disk->put('media/example.png', $this->pngBytes());

        $media = Media::factory()->create([
            'path' => 'media/example.png',
            'disk' => config('filesystems.default'),
            'mime_type' => 'image/png',
        ]);

        $this->artisan('media:backfill-webp')->assertExitCode(0);

        $disk->assertExists('media/example.webp');
    }

    #[Test]
    public function it_skips_media_that_already_has_a_webp_sibling(): void
    {
        $disk = $this->fakeDisk();
        $disk->put('media/done.png', $this->pngBytes());
        $disk->put('media/done.webp', 'pre-existing');

        Media::factory()->create([
            'path' => 'media/done.png',
            'disk' => config('filesystems.default'),
            'mime_type' => 'image/png',
        ]);

        $this->artisan('media:backfill-webp')
            ->expectsOutputToContain('already present: 1')
            ->assertExitCode(0);

        // Existing variant must be left untouched.
        $this->assertSame('pre-existing', $disk->get('media/done.webp'));
    }

    #[Test]
    public function it_ignores_non_raster_media(): void
    {
        $disk = $this->fakeDisk();
        $disk->put('media/doc.pdf', '%PDF-1.4');

        Media::factory()->create([
            'path' => 'media/doc.pdf',
            'disk' => config('filesystems.default'),
            'mime_type' => 'application/pdf',
        ]);

        $this->artisan('media:backfill-webp')
            ->expectsOutputToContain('scanned: 0')
            ->assertExitCode(0);

        $disk->assertMissing('media/doc.webp');
    }

    #[Test]
    public function dry_run_reports_without_generating(): void
    {
        $disk = $this->fakeDisk();
        $disk->put('media/preview.png', $this->pngBytes());

        Media::factory()->create([
            'path' => 'media/preview.png',
            'disk' => config('filesystems.default'),
            'mime_type' => 'image/png',
        ]);

        $this->artisan('media:backfill-webp', ['--dry-run' => true])
            ->expectsOutputToContain('would generate: media/preview.webp')
            ->assertExitCode(0);

        $disk->assertMissing('media/preview.webp');
    }
}
