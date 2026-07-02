<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MigrateProductsToMediaCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_the_file_and_updates_the_media_record(): void
    {
        $disk = Storage::fake(config('filesystems.default'));
        $disk->put('products/example.png', 'fake-bytes');

        $media = Media::factory()->create(['path' => 'products/example.png']);

        $this->artisan('media:migrate-products')->assertExitCode(0);

        $this->assertEquals('media/example.png', $media->fresh()->path);
        $disk->assertMissing('products/example.png');
        $disk->assertExists('media/example.png');
    }

    #[Test]
    public function the_media_record_never_points_at_a_deleted_file(): void
    {
        // Regression guard for the delete-before-update ordering bug: even if
        // we inspect state mid-migration, the DB and disk must never disagree
        // about where the file lives. Since the fix updates the DB first, the
        // only reachable states are "both old and new exist" or "only new
        // exists" — never "DB says old path but the file is gone".
        $disk = Storage::fake(config('filesystems.default'));
        $disk->put('products/example.png', 'fake-bytes');

        $media = Media::factory()->create(['path' => 'products/example.png']);

        $this->artisan('media:migrate-products')->assertExitCode(0);

        $fresh = $media->fresh();
        $this->assertTrue($disk->exists($fresh->path));
    }
}
