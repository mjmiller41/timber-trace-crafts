<?php

namespace Database\Seeders;

use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            [
                'state_code' => 'FL',
                'rate_percent' => '0.0600',
                'label' => 'Florida Sales Tax',
                'active' => true,
            ],
        ];

        foreach ($rates as $rate) {
            TaxRate::updateOrCreate(
                ['state_code' => $rate['state_code']],
                $rate
            );
        }
    }
}
