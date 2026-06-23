<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        view()->composer('*', function ($view) {
            $view->with('cartCount', session('cart_count', 0));

            $siteName = Cache::rememberForever('setting.store.name', fn () => Setting::get('store.name', 'Timber Trace Crafts'));
            $siteTagline = Cache::rememberForever('setting.store.tagline', fn () => Setting::get('store.tagline', 'Precision Laser-Cut Woodcrafts & Custom Decor'));
            $logoPath = Cache::rememberForever('setting.store.logo_path', fn () => Setting::get('store.logo_path', ''));
            $siteLogoUrl = $logoPath ? asset('storage/'.$logoPath) : asset('images/logo.png');

            $view->with(compact('siteName', 'siteTagline', 'siteLogoUrl'));
        });
    }
}
