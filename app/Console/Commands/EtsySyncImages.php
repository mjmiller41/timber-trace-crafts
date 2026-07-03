<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyListingImageSync;
use App\Services\Etsy\EtsyOAuthService;
use Illuminate\Console\Command;

class EtsySyncImages extends Command
{
    protected $signature = 'etsy:sync-images
                            {--product= : Only sync one product ID}
                            {--force : Upload even when the listing already has images not uploaded by us (appends, may duplicate)}';

    protected $description = 'Upload local product images to their linked Etsy listings';

    public function handle(): int
    {
        $oauth = new EtsyOAuthService;

        if (! $oauth->isConnected()) {
            $this->error('Etsy is not connected. Visit Admin → Etsy to connect.');

            return self::FAILURE;
        }

        $sync = new EtsyListingImageSync(new EtsyClient($oauth));

        $products = Product::whereNotNull('etsy_listing_id')
            ->when($this->option('product'), fn ($q, $id) => $q->where('id', $id))
            ->get();

        if ($products->isEmpty()) {
            $this->warn('No products with an Etsy listing ID found.');

            return self::FAILURE;
        }

        $uploaded = $skipped = $failed = 0;

        foreach ($products as $product) {
            $result = $sync->syncProduct($product, force: (bool) $this->option('force'));
            $uploaded += $result->created;
            $skipped += $result->skipped;
            $failed += $result->failed;

            $this->line("  #{$product->id} [{$product->etsy_listing_id}] {$result->summary()}");

            usleep(250_000);
        }

        $this->table(['Uploaded', 'Skipped', 'Failed'], [[$uploaded, $skipped, $failed]]);

        if ($skipped > 0 && ! $this->option('force')) {
            $this->line('Skipped products either have nothing new to upload or their listing already has images not uploaded via this app (use --force to append anyway).');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
