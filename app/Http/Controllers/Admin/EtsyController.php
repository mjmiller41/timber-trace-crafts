<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyInventorySync;
use App\Services\Etsy\EtsyOAuthService;
use App\Services\Etsy\EtsyOrderSync;
use App\Services\Etsy\EtsyProductSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $result = (new EtsyProductSync(new EtsyClient($this->oauth)))->syncAll();

        return redirect()->route('admin.etsy.index')
            ->with('success', 'Products synced. '.$result->summary());
    }

    public function syncInventory(): RedirectResponse
    {
        $result = (new EtsyInventorySync(new EtsyClient($this->oauth)))->syncAll();

        return redirect()->route('admin.etsy.index')
            ->with('success', 'Inventory synced. '.$result->summary());
    }

    public function syncOrders(): RedirectResponse
    {
        $result = (new EtsyOrderSync(new EtsyClient($this->oauth)))->sync();

        return redirect()->route('admin.etsy.index')
            ->with('success', 'Orders synced. '.$result->summary());
    }
}
