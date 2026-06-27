<?php

namespace App\Jobs;

use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use App\Services\Etsy\EtsyOrderSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportEtsyOrder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly string $resourceUrl) {}

    public function handle(): void
    {
        $sync = new EtsyOrderSync(new EtsyClient(app(EtsyOAuthService::class)));
        $sync->importFromResourceUrl($this->resourceUrl);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ImportEtsyOrder job failed', [
            'resource_url' => $this->resourceUrl,
            'error' => $e->getMessage(),
        ]);
    }
}
