<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => 0,
            'max_uses' => null,
            'used_count' => 0,
            'applies_to' => 'all',
            'active' => true,
        ];
    }

    public function fixed(float $amount = 5.00): static
    {
        return $this->state(['type' => 'fixed', 'value' => $amount]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    public function withMinimum(float $amount): static
    {
        return $this->state(['min_order_amount' => $amount]);
    }
}
