<?php

namespace App\Console\Commands;

use App\Models\JournalPost;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class SubmitIndexNow extends Command
{
    protected $signature = 'seo:indexnow
                            {--dry-run : List the URLs that would be submitted without calling the API}';

    protected $description = 'Submit the site\'s public URLs to IndexNow (Bing, Copilot, Yandex, et al.) to accelerate indexation';

    public function handle(): int
    {
        $key = config('services.indexnow.key');

        if (empty($key)) {
            $this->error('No IndexNow key configured. Set INDEXNOW_KEY or config/services.php.');

            return self::FAILURE;
        }

        $urls = $this->collectUrls();
        $host = parse_url(config('app.url'), PHP_URL_HOST);

        $this->info("Collected {$urls->count()} URLs for host {$host}.");

        if ($this->option('dry-run')) {
            $urls->each(fn (string $url) => $this->line("  {$url}"));
            $this->comment('Dry run — nothing submitted.');

            return self::SUCCESS;
        }

        $response = Http::acceptJson()->post('https://api.indexnow.org/indexnow', [
            'host' => $host,
            'key' => $key,
            'keyLocation' => rtrim(config('app.url'), '/')."/{$key}.txt",
            'urlList' => $urls->values()->all(),
        ]);

        if ($response->successful()) {
            $this->info("IndexNow accepted the submission (HTTP {$response->status()}).");

            return self::SUCCESS;
        }

        $this->error("IndexNow submission failed (HTTP {$response->status()}): {$response->body()}");

        return self::FAILURE;
    }

    /**
     * Gather every public, indexable URL on the site.
     *
     * @return Collection<int, string>
     */
    private function collectUrls(): Collection
    {
        $urls = collect([
            route('home'),
            route('shop'),
            route('journal.index'),
            route('contact.index'),
        ]);

        Product::where('status', 'active')
            ->pluck('slug')
            ->each(fn (string $slug) => $urls->push(route('product.show', $slug)));

        JournalPost::where('status', 'published')
            ->pluck('slug')
            ->each(fn (string $slug) => $urls->push(route('journal.show', $slug)));

        Page::pluck('slug')
            ->each(fn (string $slug) => $urls->push(route('page.show', $slug)));

        return $urls->unique();
    }
}
