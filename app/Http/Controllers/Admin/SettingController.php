<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use App\Services\Etsy\EtsyReturnPolicyService;
use App\Services\Etsy\EtsyShippingProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::all()->groupBy(function ($setting) {
            return explode('.', $setting->key)[0] ?? 'general';
        });

        $etsyShippingProfiles = $this->fetchEtsyShippingProfiles();
        $etsyReturnPolicies = $this->fetchEtsyReturnPolicies();
        $etsyDefaults = [
            'taxonomy_id' => Setting::get('etsy.taxonomy_id'),
            'shipping_profile_id' => Setting::get('etsy.shipping_profile_id'),
            'return_policy_id' => Setting::get('etsy.return_policy_id'),
        ];

        return view('admin.settings.index', compact('settings', 'etsyShippingProfiles', 'etsyReturnPolicies', 'etsyDefaults'));
    }

    private function fetchEtsyShippingProfiles(): array
    {
        try {
            return (new EtsyShippingProfileService(new EtsyClient(app(EtsyOAuthService::class))))->getProfiles();
        } catch (\Throwable) {
            return [];
        }
    }

    private function fetchEtsyReturnPolicies(): array
    {
        try {
            return (new EtsyReturnPolicyService(new EtsyClient(app(EtsyOAuthService::class))))->getPolicies();
        } catch (\Throwable) {
            return [];
        }
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            $oldPath = Setting::get('store.logo_path');
            if ($oldPath) {
                Storage::disk(config('filesystems.default'))->delete($oldPath);
            }

            $path = $request->file('logo')->store('brand', config('filesystems.default'));
            Setting::set('store.logo_path', $path);
        }

        foreach ($request->input('settings', []) as $key => $value) {
            if ($key === 'store.logo_path') {
                continue;
            }
            Setting::set($key, $value ?? '');
        }

        return redirect()->route('admin.settings.index')->with('success', 'Settings saved.');
    }
}
