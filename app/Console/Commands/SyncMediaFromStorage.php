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
        $files = Storage::disk('public')->allFiles();
        $existing = Media::pluck('path')->flip();

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        $missing = collect($files)
            ->filter(fn ($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $imageExtensions))
            ->reject(fn ($file) => $existing->has($file));

        if ($missing->isEmpty()) {
            $this->info('All storage files already have media records.');

            return self::SUCCESS;
        }

        $this->info("Found {$missing->count()} file(s) without media records. Inserting...");

        $missing->each(function (string $path) {
            $fullPath = Storage::disk('public')->path($path);
            $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
            $size = Storage::disk('public')->size($path);
            $filename = basename($path);

            Media::create([
                'filename' => $filename,
                'original_name' => $filename,
                'disk' => 'public',
                'path' => $path,
                'mime_type' => $mimeType,
                'size_bytes' => $size,
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
}
