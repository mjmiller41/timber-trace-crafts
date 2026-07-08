<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyInventorySync;
use App\Services\Etsy\EtsyListingDiff;
use App\Services\Etsy\EtsyOAuthService;
use App\Services\Etsy\EtsyOrderSync;
use App\Services\Etsy\EtsyProductSync;
use App\Services\Etsy\EtsyReviewSync;
use App\Services\Etsy\EtsyShopSectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EtsyController extends Controller
{
    public function __construct(private readonly EtsyOAuthService $oauth) {}

    public function index(): View
    {
        return view('admin.etsy.index', [
            'isConnected' => $this->oauth->isConnected(),
            'shopId' => Setting::get('etsy.shop_id'),
            'tokenExpiresAt' => Setting::get('etsy.token_expires_at'),
            'ordersLastSyncedAt' => Setting::get('etsy.orders_last_synced_at'),
            'diffReport' => Cache::get('etsy.product_diff'),
        ]);
    }

    public function connect(): RedirectResponse
    {
        return redirect($this->oauth->buildAuthUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('admin.etsy.index')
                ->with('error', 'Etsy authorization was denied: '.$request->input('error_description', ''));
        }

        try {
            $this->oauth->handleCallback(
                $request->input('code'),
                $request->input('state')
            );

            return redirect()->route('admin.etsy.index')->with('success', 'Connected to Etsy successfully.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.etsy.index')->with('error', 'Connection failed: '.$e->getMessage());
        }
    }

    public function disconnect(): RedirectResponse
    {
        foreach (['etsy.access_token', 'etsy.refresh_token', 'etsy.token_expires_at', 'etsy.shop_id', 'etsy.orders_last_synced_at'] as $key) {
            Setting::set($key, null);
        }

        return redirect()->route('admin.etsy.index')->with('success', 'Disconnected from Etsy.');
    }

    public function syncProducts(): RedirectResponse
    {
        try {
            $result = (new EtsyProductSync(new EtsyClient($this->oauth)))->syncAll();

            return redirect()->route('admin.etsy.index')->with('success', 'Products synced. '.$result->summary());
        } catch (\Throwable $e) {
            return redirect()->route('admin.etsy.index')->with('error', 'Product sync failed: '.$e->getMessage());
        }
    }

    public function pushProduct(Product $product): RedirectResponse
    {
        try {
            (new EtsyProductSync(new EtsyClient($this->oauth)))->syncProduct($product);

            $action = $product->etsy_listing_id ? 'updated' : 'created';

            return redirect()->route('admin.products.edit', $product)
                ->with('success', "Pushed to Etsy ({$action}) — listing #{$product->etsy_listing_id}");
        } catch (\Throwable $e) {
            Log::error('Etsy product push failed', ['product_id' => $product->id, 'error' => $e->getMessage()]);

            return redirect()->route('admin.products.edit', $product)
                ->with('error', 'Etsy push failed: '.$e->getMessage());
        }
    }

    public function syncInventory(): RedirectResponse
    {
        try {
            $result = (new EtsyInventorySync(new EtsyClient($this->oauth)))->syncAll();

            return redirect()->route('admin.etsy.index')->with('success', 'Inventory synced. '.$result->summary());
        } catch (\Throwable $e) {
            return redirect()->route('admin.etsy.index')->with('error', 'Inventory sync failed: '.$e->getMessage());
        }
    }

    public function syncOrders(): RedirectResponse
    {
        try {
            $result = (new EtsyOrderSync(new EtsyClient($this->oauth)))->sync();

            return redirect()->route('admin.etsy.index')->with('success', 'Orders synced. '.$result->summary());
        } catch (\Throwable $e) {
            return redirect()->route('admin.etsy.index')->with('error', 'Order sync failed: '.$e->getMessage());
        }
    }

    public function syncReviews(): RedirectResponse
    {
        try {
            $result = (new EtsyReviewSync(new EtsyClient($this->oauth)))->sync();

            return redirect()->route('admin.etsy.index')->with('success', 'Reviews synced. '.$result->summary());
        } catch (\Throwable $e) {
            return redirect()->route('admin.etsy.index')->with('error', 'Review sync failed: '.$e->getMessage());
        }
    }

    public function diffProducts(): RedirectResponse
    {
        try {
            $report = (new EtsyListingDiff(new EtsyClient($this->oauth)))->diff();

            Cache::put('etsy.product_diff', $report, now()->addHour());

            $summary = count($report['conflicts']).' conflict(s), '
                .count($report['etsyOnly']).' Etsy-only, '
                .count($report['dbOnly']).' website-only, '
                .$report['matched'].' matched.';

            return redirect()->route('admin.etsy.index')->with('success', 'Diff complete. '.$summary);
        } catch (\Throwable $e) {
            return redirect()->route('admin.etsy.index')->with('error', 'Diff failed: '.$e->getMessage());
        }
    }

    public function resolveDiff(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resolutions' => ['sometimes', 'array'],
            'resolutions.*' => ['array'],
            'resolutions.*.*' => ['in:db,etsy'],
        ]);

        $resolutions = $validated['resolutions'] ?? [];

        if (empty($resolutions)) {
            return redirect()->route('admin.etsy.index')->with('error', 'No resolutions selected.');
        }

        $report = Cache::get('etsy.product_diff');

        if (! $report) {
            return redirect()->route('admin.etsy.index')->with('error', 'Diff report expired — run the diff again.');
        }

        $conflictsByProduct = collect($report['conflicts'])->keyBy('product_id');
        $diffService = new EtsyListingDiff(new EtsyClient($this->oauth));
        $productSync = new EtsyProductSync(new EtsyClient($this->oauth));

        $resolvedFields = 0;
        $resolvedProducts = 0;
        $failures = [];

        foreach ($resolutions as $productId => $fields) {
            $conflict = $conflictsByProduct->get((int) $productId);
            $product = Product::with('variants')->find($productId);

            if (! $conflict || ! $product) {
                continue;
            }

            try {
                $keepWebsite = false;

                foreach ($fields as $field => $choice) {
                    if (! isset($conflict['differences'][$field])) {
                        continue;
                    }

                    if ($choice === 'etsy') {
                        $diffService->applyEtsyValue($product, $field, $conflict['differences'][$field]['etsy']);
                    } else {
                        $keepWebsite = true;
                    }

                    $resolvedFields++;
                }

                // Etsy-side merges were applied to the DB first, so a single push
                // converges both sides on the chosen merged state. The push sends
                // the full payload, so it also resolves any unpicked fields on
                // this product toward the website values.
                if ($keepWebsite) {
                    $productSync->syncProduct($product->refresh());
                    $conflictsByProduct->forget((int) $productId);
                } else {
                    $remaining = array_diff_key($conflict['differences'], $fields);

                    if (empty($remaining)) {
                        $conflictsByProduct->forget((int) $productId);
                    } else {
                        $conflict['differences'] = $remaining;
                        $conflictsByProduct->put((int) $productId, $conflict);
                    }
                }

                $resolvedProducts++;
            } catch (\Throwable $e) {
                Log::error('Etsy diff resolution failed', ['product_id' => $product->id, 'error' => $e->getMessage()]);
                $failures[] = "{$product->name}: {$e->getMessage()}";
            }
        }

        $report['conflicts'] = $conflictsByProduct->values()->all();
        Cache::put('etsy.product_diff', $report, now()->addHour());

        if (! empty($failures)) {
            return redirect()->route('admin.etsy.index')
                ->with('error', 'Some resolutions failed — '.implode(' | ', $failures));
        }

        return redirect()->route('admin.etsy.index')
            ->with('success', "Resolved {$resolvedFields} field(s) across {$resolvedProducts} product(s).");
    }

    public function listSections(): JsonResponse
    {
        try {
            $sections = (new EtsyShopSectionService(new EtsyClient($this->oauth)))->getSections();

            return response()->json($sections);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function ordersBadge(): JsonResponse
    {
        return response()->json(['count' => (int) Cache::get('etsy.new_orders', 0)]);
    }

    public function createSection(Request $request): JsonResponse
    {
        $title = trim((string) $request->input('title', ''));

        if ($title === '') {
            return response()->json(['error' => 'Section title is required.'], 422);
        }

        try {
            $section = (new EtsyShopSectionService(new EtsyClient($this->oauth)))->createSection($title);

            return response()->json($section);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
