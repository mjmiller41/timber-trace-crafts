<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\Setting;
use App\Observers\ProductObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImageManager::class, fn () => new ImageManager(GdDriver::class));
    }

    public function boot(): void
    {
        Product::observe(ProductObserver::class);

        view()->composer('*', function ($view) {
            $view->with('cartCount', session('cart_count', 0));

            $siteName = Cache::rememberForever('setting.store.name', fn () => Setting::get('store.name', 'Timber Trace Crafts'));
            $siteTagline = Cache::rememberForever('setting.store.tagline', fn () => Setting::get('store.tagline', 'Precision Laser-Cut Woodcrafts & Custom Decor'));
            $logoPath = Cache::rememberForever('setting.store.logo_path', fn () => Setting::get('store.logo_path', ''));
            $siteLogoUrl = $logoPath ? Storage::disk(config('filesystems.default'))->url($logoPath) : asset('images/logo.png');

            $socialInstagram = Cache::rememberForever('setting.social.instagram_url', fn () => Setting::get('social.instagram_url', ''));
            $socialFacebook = Cache::rememberForever('setting.social.facebook_url', fn () => Setting::get('social.facebook_url', ''));
            $socialPinterest = Cache::rememberForever('setting.social.pinterest_url', fn () => Setting::get('social.pinterest_url', ''));

            $view->with(compact('siteName', 'siteTagline', 'siteLogoUrl', 'socialInstagram', 'socialFacebook', 'socialPinterest'));
        });
    }
}
