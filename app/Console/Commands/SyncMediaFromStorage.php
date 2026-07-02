<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncMediaFromStorage extends Command
{
    protected $signature = 'media:sync';

    protected $description = 'Insert media records for any files in storage that are missing from the database';

    public function handle(): int
    {
        $disk = config('filesystems.default');
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
        ];

        $files = Storage::disk($disk)->allFiles();
        $existing = Media::pluck('path')->flip();

        $missing = collect($files)
            ->filter(fn ($file) => array_key_exists(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $mimeMap))
            ->reject(fn ($file) => $existing->has($file))
            ->reject(fn ($file) => $this->isGeneratedWebpVariant($file, $files));

        if ($missing->isEmpty()) {
            $this->info('All storage files already have media records.');

            return self::SUCCESS;
        }

        $this->info("Found {$missing->count()} file(s) without media records. Inserting...");

        $missing->each(function (string $path) use ($disk, $mimeMap) {
            $filename = basename($path);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            Media::create([
                'filename' => $filename,
                'original_name' => $filename,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $mimeMap[$ext] ?? 'application/octet-stream',
                'size_bytes' => Storage::disk($disk)->size($path),
                'alt_text' => Str::of(pathinfo($filename, PATHINFO_FILENAME))
                    ->replace(['-', '_'], ' ')
                    ->title()
                    ->toString(),
                'uploaded_by' => null,
            ]);

            $this->line("  + {$path}");
        });

        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * `media:backfill-webp` generates a `.webp` sibling alongside the original
     * raster file on disk, but that sibling is derived (via URL substitution,
     * see Product::getPrimaryImageUrlAttribute) and never gets its own Media
     * row — only the original does. Without this check, every backfilled
     * sibling would be inserted as a spurious duplicate Media record.
     *
     * @param  list<string>  $files
     */
    private function isGeneratedWebpVariant(string $path, array $files): bool
    {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'webp') {
            return false;
        }

        $base = preg_replace('/\.webp$/i', '', $path);

        foreach (['png', 'jpg', 'jpeg'] as $ext) {
            if (in_array("{$base}.{$ext}", $files, true)) {
                return true;
            }
        }

        return false;
    }
}
