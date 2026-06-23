<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\ShippingMethod;

class ShippingService
{
    public static function getAvailableMethods(): array
    {
        $priorityUpcharge = (float) Setting::get('shipping.priority_upcharge', 7.00);
        $expressUpcharge = (float) Setting::get('shipping.priority_express_upcharge', 35.00);

        return ShippingMethod::where('active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($method) use ($priorityUpcharge, $expressUpcharge) {
                $price = match ($method->service_code) {
                    'usps_ground_advantage' => 0.00,
                    'usps_priority' => $priorityUpcharge,
                    'usps_priority_express' => $expressUpcharge,
                    default => (float) ($method->price_override ?? 0),
                };

                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'service_code' => $method->service_code,
                    'description' => $method->description,
                    'price' => $price,
                    'is_free' => $price === 0.00,
                ];
            })
            ->toArray();
    }
}
