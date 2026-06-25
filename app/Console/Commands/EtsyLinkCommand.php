<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class EtsyLinkCommand extends Command
{
    protected $signature = 'etsy:link
                            {product_id : Local product ID}
                            {etsy_listing_id : Etsy listing ID}
                            {--activate : Also set status to active}
                            {--etsy-data : Also pull and store Etsy metadata fields}';

    protected $description = 'Link an existing product to its Etsy listing ID';

    public function handle(): int
    {
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

        $updates = ['etsy_listing_id' => $listingId];

        if ($this->option('activate')) {
            $updates['status'] = 'active';
        }

        $product->update($updates);

        $status = $this->option('activate') ? ' → active' : '';
        $this->info("✓ Product #{$product->id} \"{$product->name}\" linked to Etsy listing {$listingId}{$status}");

        return 0;
    }
}
