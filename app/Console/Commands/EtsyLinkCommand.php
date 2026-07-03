<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class EtsyLinkCommand extends Command
{
    protected $signature = 'etsy:link
                            {product_id : Local product ID}
                            {etsy_listing_id : Etsy listing ID}
                            {--activate : Also set status to active}';

    protected $description = 'Link an existing product to an Etsy listing, syncing Etsy name/description/metadata';

    public function handle(EtsyOAuthService $oauth): int
    {
        if (! $oauth->isConnected()) {
            $this->error('Etsy is not connected. Go to Admin → Etsy to authorize.');

            return 1;
        }

        $product = Product::find($this->argument('product_id'));

        if (! $product) {
            $this->error("Product #{$this->argument('product_id')} not found.");

            return 1;
        }

        $listingId = $this->argument('etsy_listing_id');

        $conflict = Product::where('etsy_listing_id', $listingId)
            ->where('id', '!=', $product->id)
            ->first();

        if ($conflict) {
            $this->error("Listing {$listingId} is already linked to product #{$conflict->id} ({$conflict->name}).");

            return 1;
        }

        $this->line("  Fetching Etsy listing {$listingId}...");

        $client = new EtsyClient($oauth);
        $listing = $client->get("/application/listings/{$listingId}");

        if (empty($listing)) {
            $this->error("Listing {$listingId} not found on Etsy.");

            return 1;
        }

        // Readiness state lives on the inventory offerings, not the listing itself,
        // and EtsyInventorySync requires it — so copy it as part of the link.
        $inventory = $client->get("/application/listings/{$listingId}/inventory");
        $readinessStateId = collect($inventory['products'] ?? [])
            ->flatMap(fn (array $p) => $p['offerings'] ?? [])
            ->pluck('readiness_state_id')
            ->filter()
            ->first();

        // The Etsy API returns text HTML-encoded (&#39; etc.); decode so the DB
        // holds the real text shown on Etsy.
        $newName = html_entity_decode($listing['title'] ?? $product->name, ENT_QUOTES | ENT_HTML5);
        $newDescription = isset($listing['description'])
            ? html_entity_decode($listing['description'], ENT_QUOTES | ENT_HTML5)
            : $product->description;
        $newSlug = $this->uniqueSlug(Str::slug($newName), $product->id);

        $updates = [
            'etsy_listing_id' => $listingId,
            'name' => $newName,
            'slug' => $newSlug,
            'description' => $newDescription,
            'short_description' => Str::limit(strip_tags($newDescription ?? ''), 200) ?: $product->short_description,
            'etsy_state' => $listing['state'] ?? null,
            'etsy_tags' => $listing['tags'] ?? [],
            'etsy_materials' => $listing['materials'] ?? [],
            'etsy_who_made' => $listing['who_made'] ?? null,
            'etsy_when_made' => $listing['when_made'] ?? null,
            'etsy_is_supply' => $listing['is_supply'] ?? null,
            'etsy_processing_min' => $listing['processing_min'] ?? null,
            'etsy_processing_max' => $listing['processing_max'] ?? null,
            'etsy_item_weight' => $listing['item_weight'] ?? null,
            'etsy_item_weight_unit' => $listing['item_weight_unit'] ?? null,
            'etsy_item_length' => $listing['item_length'] ?? null,
            'etsy_item_width' => $listing['item_width'] ?? null,
            'etsy_item_height' => $listing['item_height'] ?? null,
            'etsy_item_dimensions_unit' => $listing['item_dimensions_unit'] ?? null,
            'etsy_taxonomy_id' => $listing['taxonomy_id'] ?? null,
            'etsy_shop_section_id' => $listing['shop_section_id'] ?? null,
            'etsy_shipping_profile_id' => $listing['shipping_profile_id'] ?? null,
            'etsy_return_policy_id' => $listing['return_policy_id'] ?? null,
            'etsy_readiness_state_id' => $readinessStateId,
        ];

        if ($this->option('activate')) {
            $updates['status'] = 'active';
        }

        $product->update($updates);

        $status = $this->option('activate') ? ' → active' : '';
        $this->info("✓ #{$product->id} linked to [{$listingId}]{$status}");
        $this->line("  Name: {$newName}");

        return 0;
    }

    private function uniqueSlug(string $base, int $excludeId): string
    {
        $slug = $base;
        $i = 2;

        while (Product::where('slug', $slug)->where('id', '!=', $excludeId)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
