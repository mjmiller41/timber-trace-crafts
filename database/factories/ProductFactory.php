<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'sku_base' => strtoupper(Str::random(6)),
            'price' => fake()->randomFloat(2, 10, 200),
            'status' => 'active',
            'personalization_type' => 'none',
            'featured' => false,
            'sort_order' => 0,
        ];
    }
}
