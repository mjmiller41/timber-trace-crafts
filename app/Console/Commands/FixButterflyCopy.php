<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class FixButterflyCopy extends Command
{
    protected $signature = 'catalog:fix-butterfly-copy
        {--dry-run : Report what would change without writing to the database}';

    protected $description = 'Repair the butterfly-earrings copy-paste error: rewrite stray "teardrop" wording in butterfly-named products to "butterfly" wording (the genuine Teardrop Earrings product is left untouched).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Only butterfly-named products are in scope. The genuine "Teardrop
        // Earrings" product is NOT named butterfly, so its legitimate teardrop
        // copy is never rewritten. (A22)
        $products = Product::query()
            ->where('name', 'like', '%butterfly%')
            ->where(function ($q) {
                $q->where('description', 'like', '%teardrop%')
                    ->orWhere('short_description', 'like', '%teardrop%');
            })
            ->get();

        $fixed = 0;

        foreach ($products as $product) {
            $dirty = false;

            foreach (['description', 'short_description'] as $field) {
                $original = $product->{$field};
                if ($original === null || $original === '') {
                    continue;
                }

                $rewritten = $this->rewrite($original);
                if ($rewritten !== $original) {
                    $product->{$field} = $rewritten;
                    $dirty = true;
                }
            }

            if (! $dirty) {
                continue;
            }

            $fixed++;
            $this->line(($dryRun ? '[dry-run] ' : '')."Fixed copy for #{$product->id} — {$product->name}");

            if (! $dryRun) {
                $product->save();
            }
        }

        $this->info(($dryRun ? 'Would fix ' : 'Fixed ')."{$fixed} product(s).");

        return self::SUCCESS;
    }

    /**
     * Replace the word "teardrop" with "butterfly", preserving the original
     * capitalization pattern of each match (Teardrop → Butterfly,
     * teardrop → butterfly, TEARDROP → BUTTERFLY).
     */
    private function rewrite(string $text): string
    {
        return preg_replace_callback('/teardrop/i', function (array $m): string {
            $match = $m[0];

            if (ctype_upper($match)) {
                return 'BUTTERFLY';
            }

            if (ctype_upper($match[0])) {
                return 'Butterfly';
            }

            return 'butterfly';
        }, $text);
    }
}
