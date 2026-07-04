<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $settings = [
            // Store / Brand
            ['key' => 'store.name',     'value' => 'Timber Trace Crafts',                              'group' => 'store', 'label' => 'Store Name'],
            ['key' => 'store.tagline',  'value' => 'Precision Laser-Cut Woodcrafts & Custom Decor',    'group' => 'store', 'label' => 'Tagline'],
            ['key' => 'store.logo_path', 'value' => '',                                                  'group' => 'store', 'label' => 'Logo'],
            ['key' => 'store.email',    'value' => 'hello@timbertracecrafts.com',                      'group' => 'store', 'label' => 'Store Email'],
            ['key' => 'store.phone',    'value' => '',                                                  'group' => 'store', 'label' => 'Store Phone'],

            // Social
            ['key' => 'social.instagram_url', 'value' => '', 'group' => 'social', 'label' => 'Instagram URL'],
            ['key' => 'social.facebook_url',  'value' => '', 'group' => 'social', 'label' => 'Facebook URL'],
            ['key' => 'social.pinterest_url', 'value' => '', 'group' => 'social', 'label' => 'Pinterest URL'],
            ['key' => 'social.youtube_url',   'value' => '', 'group' => 'social', 'label' => 'YouTube URL'],
            ['key' => 'social.linkedin_url',  'value' => '', 'group' => 'social', 'label' => 'LinkedIn URL'],

            // Shipping
            ['key' => 'shipping.priority_upcharge',         'value' => '7.00',  'group' => 'shipping', 'label' => 'Priority Mail Upcharge'],
            ['key' => 'shipping.priority_express_upcharge', 'value' => '35.00', 'group' => 'shipping', 'label' => 'Priority Express Upcharge'],

            // Tax
            ['key' => 'tax.apply_to_states', 'value' => 'FL', 'group' => 'tax', 'label' => 'Apply Tax to States (comma-separated)'],

            // Email
            ['key' => 'email.order_from_name',    'value' => 'Timber Trace Crafts',        'group' => 'email', 'label' => 'Order Email From Name'],
            ['key' => 'email.order_from_address', 'value' => 'hello@timbertracecrafts.com', 'group' => 'email', 'label' => 'Order Email From Address'],

            // Inventory
            ['key' => 'low_stock.default_threshold', 'value' => '5', 'group' => 'inventory', 'label' => 'Default Low Stock Threshold'],

            // Payments — Stripe
            ['key' => 'stripe.publishable_key', 'value' => '', 'group' => 'payments', 'label' => 'Stripe Publishable Key'],
            ['key' => 'stripe.mode',            'value' => 'test', 'group' => 'payments', 'label' => 'Stripe Mode (test/live)'],

            // Analytics — Umami
            ['key' => 'umami.script_url',  'value' => '', 'group' => 'analytics', 'label' => 'Umami Script URL'],
            ['key' => 'umami.website_id',  'value' => '', 'group' => 'analytics', 'label' => 'Umami Website ID'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                array_merge($setting, ['updated_at' => $now])
            );
        }
    }
}
