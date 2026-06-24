<?php

namespace App\Console\Commands;

use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyInventorySync;
use App\Services\Etsy\EtsyOAuthService;
use Illuminate\Console\Command;

class EtsySyncInventory extends Command
{
    protected $signature = 'etsy:sync-inventory';

    protected $description = 'Push variant stock quantities for all Etsy-linked products';

    public function handle(): int
    {
        $oauth = new EtsyOAuthService;

        if (! $oauth->isConnected()) {
            $this->error('Etsy is not connected. Visit Admin → Etsy to connect.');

            return self::FAILURE;
        }

        $this->info('Syncing inventory to Etsy...');

        $sync = new EtsyInventorySync(new EtsyClient($oauth));
        $result = $sync->syncAll();

        $this->table(['Created', 'Updated', 'Skipped', 'Failed'], [
            [$result->created, $result->updated, $result->skipped, $result->failed],
        ]);

        return $result->failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
