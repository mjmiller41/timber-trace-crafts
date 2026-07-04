<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Console\Command;

/**
 * One-time (idempotent) backfill of the launch catalog's `etsy_materials` and
 * storefront tag relations. These values previously lived only on Etsy, so
 * `etsy:sync-products` pushed empty materials and the storefront had no browse
 * tags (see TIM-10).
 *
 * Materials are taken verbatim from each listing's own "Item Details" copy so
 * they match what buyers already see on Etsy. Storefront tags map to the
 * existing Tag rows (gifts / handmade / occasions / personalization / wood-jewelry).
 *
 * Data-only: this command makes **no Etsy API calls** and writes with quiet
 * saves so it never trips ProductObserver / SyncProductToEtsy. Pushing the new
 * materials to live listings is a separate, prod-owned `etsy:sync-products` run.
 */
class EtsyBackfillMaterials extends Command
{
    protected $signature = 'etsy:backfill-materials {--apply : Persist the changes (default is a dry run)}';

    protected $description = 'Backfill etsy_materials + storefront tags on the launch catalog (data-only, no Etsy API calls).';

    /**
     * Keyed by sku_base — the stable identity of each launch product.
     *
     * @var array<string, array{materials: list<string>, tags: list<string>}>
     */
    private const EARRINGS = [
        'materials' => ['Solid Hardwood', 'Danish Oil', 'Gold Ear Wires'],
        'tags' => ['gifts', 'handmade', 'wood-jewelry'],
    ];

    private function plan(): array
    {
        return [
            'TMBLR-AM250-STNLS20' => [
                'materials' => ['Stainless Steel', 'Plastic', 'Rubber'],
                'tags' => ['gifts', 'handmade', 'occasions'],
            ],
            'BOX-HRT-BBPLY3' => [
                'materials' => ['Baltic Birch Plywood', 'Felt', 'Wood Stain'],
                'tags' => ['gifts', 'handmade', 'occasions', 'personalization'],
            ],
            'EAR-BFLY-3-01' => self::EARRINGS,
            'EAR-BFLY-3-02' => self::EARRINGS,
            'EAR-BFLY-3-03' => self::EARRINGS,
            'EAR-TDRP-' => self::EARRINGS,
        ];
    }

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $tagIdBySlug = Tag::pluck('id', 'slug');

        $rows = [];
        $changed = 0;

        foreach ($this->plan() as $sku => $spec) {
            $product = Product::with('tags')->where('sku_base', $sku)->first();

            if (! $product) {
                $rows[] = [$sku, 'MISSING', '—', '—'];

                continue;
            }

            // Guard: every planned tag slug must exist as a Tag row.
            $tagIds = [];
            $missingTag = null;
            foreach ($spec['tags'] as $slug) {
                if (! isset($tagIdBySlug[$slug])) {
                    $missingTag = $slug;
                    break;
                }
                $tagIds[] = $tagIdBySlug[$slug];
            }
            if ($missingTag !== null) {
                $rows[] = [$sku, "TAG MISSING: {$missingTag}", '—', '—'];

                continue;
            }

            $currentMaterials = $product->etsy_materials ?? [];
            $currentTagIds = $product->tags->pluck('id')->sort()->values()->all();
            $wantTagIds = collect($tagIds)->sort()->values()->all();

            $materialsChange = $currentMaterials !== $spec['materials'];
            $tagsChange = $currentTagIds !== $wantTagIds;

            $status = ($materialsChange || $tagsChange) ? ($apply ? 'UPDATED' : 'WILL UPDATE') : 'up-to-date';
            if ($materialsChange || $tagsChange) {
                $changed++;
            }

            if ($apply && ($materialsChange || $tagsChange)) {
                if ($materialsChange) {
                    // Quiet write: no ProductObserver, no queued Etsy push, no token use.
                    $product->updateQuietly(['etsy_materials' => $spec['materials']]);
                }
                if ($tagsChange) {
                    // sync() touches only the product_tags pivot; it does not fire the Product saved event.
                    $product->tags()->sync($tagIds);
                }
            }

            $rows[] = [
                $sku,
                $status,
                implode(', ', $spec['materials']),
                implode(', ', $spec['tags']),
            ];
        }

        $this->table(['sku_base', 'status', 'etsy_materials', 'storefront tags'], $rows);

        if (! $apply) {
            $this->warn("Dry run — {$changed} product(s) would change. Re-run with --apply to persist.");
        } else {
            $this->info("Done — {$changed} product(s) updated. Run etsy:sync-products (prod) to push materials to live listings.");
        }

        return self::SUCCESS;
    }
}
