<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\TaxRate;

class TaxService
{
    public function calculate(float $subtotal, string $shippingState): float
    {
        $taxableStates = array_map('trim', explode(',', Setting::get('tax.apply_to_states', 'FL')));

        if (! in_array(strtoupper($shippingState), $taxableStates)) {
            return 0.0;
        }

        $rate = TaxRate::where('state_code', strtoupper($shippingState))
            ->where('active', true)
            ->value('rate_percent');

        if (! $rate) {
            return 0.0;
        }

        return round($subtotal * (float) $rate, 2);
    }
}
