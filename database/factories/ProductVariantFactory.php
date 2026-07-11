<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => strtoupper(Str::random(8)),
            'mpn' => null,
            'label' => fake()->randomElement(['Small', 'Medium', 'Large', 'Natural', 'Dark Walnut']),
            'stock_qty' => fake()->numberBetween(0, 50),
            'low_stock_threshold' => 5,
            'sort_order' => 0,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(['stock_qty' => 0]);
    }

    /**
     * Give the variant an explicit manufacturer part number.
     */
    public function withMpn(string $mpn): static
    {
        return $this->state(['mpn' => $mpn]);
    }
}
