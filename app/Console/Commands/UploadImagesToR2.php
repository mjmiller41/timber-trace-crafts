<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class UploadImagesToR2 extends Command
{
    protected $signature = 'media:upload-to-r2';

    protected $description = 'Upload all local storage images to Cloudflare R2 and update media records';

    public function handle(): int
    {
        $files = Storage::disk('public')->allFiles();
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        $images = collect($files)->filter(
            fn ($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $imageExtensions)
        );

        $this->info("Uploading {$images->count()} images to R2...");
        $bar = $this->output->createProgressBar($images->count());

        $images->each(function (string $path) use ($bar) {
            $contents = Storage::disk('public')->get($path);
            $mimeType = Storage::disk('public')->mimeType($path);

            Storage::disk('r2')->put($path, $contents, [
                'ContentType' => $mimeType,
                'visibility' => 'public',
            ]);

            $bar->advance();
        });

        $bar->finish();
        $this->newLine();

        $updated = Media::where('disk', 'public')->update(['disk' => 'r2']);
        $this->info("Updated {$updated} media records to use R2 disk.");

        return self::SUCCESS;
    }
}
