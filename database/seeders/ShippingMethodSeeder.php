<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'USPS Ground Advantage',
                'carrier' => 'usps',
                'service_code' => 'usps_ground_advantage',
                'description' => 'Delivered in 2–5 business days.',
                'price_override' => null,
                'is_free_base' => true,
                'sort_order' => 0,
                'active' => true,
            ],
            [
                'name' => 'USPS Priority Mail',
                'carrier' => 'usps',
                'service_code' => 'usps_priority',
                'description' => 'Delivered in 1–3 business days.',
                'price_override' => null,
                'is_free_base' => false,
                'sort_order' => 1,
                'active' => true,
            ],
            [
                'name' => 'USPS Priority Mail Express',
                'carrier' => 'usps',
                'service_code' => 'usps_priority_express',
                'description' => 'Overnight delivery guaranteed.',
                'price_override' => null,
                'is_free_base' => false,
                'sort_order' => 2,
                'active' => true,
            ],
        ];

        foreach ($methods as $method) {
            ShippingMethod::updateOrCreate(
                ['service_code' => $method['service_code']],
                $method
            );
        }
    }
}
