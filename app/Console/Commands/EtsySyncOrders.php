<?php

namespace App\Console\Commands;

use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use App\Services\Etsy\EtsyOrderSync;
use Illuminate\Console\Command;

class EtsySyncOrders extends Command
{
    protected $signature = 'etsy:sync-orders';

    protected $description = 'Pull new orders from Etsy into the local database';

    public function handle(): int
    {
        $oauth = new EtsyOAuthService;

        if (! $oauth->isConnected()) {
            $this->error('Etsy is not connected. Visit Admin → Etsy to connect.');

            return self::FAILURE;
        }

        $this->info('Pulling orders from Etsy...');

        $sync = new EtsyOrderSync(new EtsyClient($oauth));
        $result = $sync->sync();

        $this->table(['Imported', 'Updated', 'Skipped', 'Failed'], [
            [$result->created, $result->updated, $result->skipped, $result->failed],
        ]);

        return $result->failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
