<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateProductsToMedia extends Command
{
    protected $signature = 'media:migrate-products
                            {--dry-run : List files without moving them}';

    protected $description = 'Move all files from products/ to media/ on R2 and update DB records';

    public function handle(): int
    {
        $disk = config('filesystems.default');
        $files = collect(Storage::disk($disk)->allFiles('products'));

        if ($files->isEmpty()) {
            $this->info('No files found in products/.');

            return self::SUCCESS;
        }

        $dryRun = $this->option('dry-run');
        $this->info(($dryRun ? '[DRY RUN] ' : '')."Found {$files->count()} file(s) to migrate.");

        $moved = 0;
        $failed = 0;

        foreach ($files as $oldPath) {
            $filename = basename($oldPath);
            $newPath = 'media/'.$filename;

            if ($dryRun) {
                $this->line("  {$oldPath} → {$newPath}");

                continue;
            }

            if (Storage::disk($disk)->exists($newPath)) {
                $this->warn("  SKIP (already exists): {$newPath}");

                continue;
            }

            if (Storage::disk($disk)->copy($oldPath, $newPath)) {
                // Update the DB record before deleting the source — if this crashes
                // mid-migration, worst case is a harmless duplicate file rather than
                // a Media row pointing at a path that no longer exists.
                Media::where('path', $oldPath)->update(['path' => $newPath]);
                Storage::disk($disk)->delete($oldPath);

                $this->line("  ✓ {$oldPath} → {$newPath}");
                $moved++;
            } else {
                $this->error("  ✗ Failed: {$oldPath}");
                $failed++;
            }
        }

        if ($dryRun) {
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("Done. Moved: {$moved}, Failed: {$failed}.");

        if ($failed === 0) {
            $this->warn('Remember to update any hardcoded products/ paths in your views.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
