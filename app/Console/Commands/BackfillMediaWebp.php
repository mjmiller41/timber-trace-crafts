<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\Media\MediaUploader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class BackfillMediaWebp extends Command
{
    protected $signature = 'media:backfill-webp
                            {--dry-run : Report missing WebP variants without generating them}';

    protected $description = 'Generate the WebP sibling for any raster Media record that is missing one';

    public function handle(MediaUploader $uploader): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $scanned = 0;
        $skipped = 0;
        $generated = 0;
        $failed = 0;

        Media::query()->each(function (Media $media) use ($uploader, $dryRun, &$scanned, &$skipped, &$generated, &$failed) {
            $webpPath = MediaUploader::webpPath($media->path);

            if ($webpPath === null) {
                return;
            }

            $scanned++;

            if ($this->webpExists($media, $webpPath)) {
                $skipped++;

                return;
            }

            if ($dryRun) {
                $generated++;
                $this->line("  would generate: {$webpPath}");

                return;
            }

            $contents = $this->sourceContents($media);

            if ($contents !== null && $uploader->generateWebpVariant($media->disk, $media->path, $contents) !== null) {
                $generated++;
                $this->line("  + {$webpPath}");
            } else {
                $failed++;
                $this->warn("  ! failed: {$media->path}");
            }
        });

        $verb = $dryRun ? 'missing' : 'generated';
        $this->info("Raster media scanned: {$scanned} | already present: {$skipped} | {$verb}: {$generated} | failed: {$failed}");

        return self::SUCCESS;
    }

    /**
     * Determine whether a WebP sibling already exists for the media record.
     *
     * Some S3-compatible disks (notably Cloudflare R2 with scoped tokens) cannot
     * perform a HeadObject, so Flysystem's exists() throws rather than returning
     * a boolean. In that case fall back to a HEAD against the public URL.
     */
    private function webpExists(Media $media, string $webpPath): bool
    {
        try {
            return Storage::disk($media->disk)->exists($webpPath);
        } catch (\Throwable) {
            $url = preg_replace('/\.(png|jpe?g)$/i', '.webp', $media->url());

            return Http::head($url)->successful();
        }
    }

    /**
     * Fetch the original image bytes. Prefers the storage disk, but falls back
     * to the public URL for disks whose token cannot GetObject (e.g. R2 tokens
     * scoped to writes only, which still serve reads via the public bucket URL).
     */
    private function sourceContents(Media $media): ?string
    {
        try {
            if (($contents = Storage::disk($media->disk)->get($media->path)) !== null) {
                return $contents;
            }
        } catch (\Throwable) {
            // Fall through to the public URL.
        }

        $response = Http::get($media->url());

        return $response->successful() ? $response->body() : null;
    }
}
