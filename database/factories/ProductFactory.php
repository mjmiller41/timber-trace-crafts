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
            'specs' => null,
            'gtin13' => null,
            'identifier_exists' => true,
        ];
    }

    /**
     * Give the product structured specs (material, weight, dimensions, capacity).
     */
    public function withSpecs(?array $specs = null): static
    {
        return $this->state(fn () => [
            'specs' => $specs ?? [
                'material' => 'Stainless Steel',
                'weight' => ['value' => 0.8, 'unit' => 'lb'],
                'dimensions' => ['value' => '3.5 x 3.5 x 7', 'unit' => 'in'],
                'capacity' => ['value' => 20, 'unit' => 'oz'],
            ],
        ]);
    }

    /**
     * Give the product a GTIN-13 identifier (identifiers exist).
     */
    public function withGtin(string $gtin13 = '0123456789012'): static
    {
        return $this->state(fn () => [
            'gtin13' => $gtin13,
            'identifier_exists' => true,
        ]);
    }

    /**
     * Handmade product with no manufacturer identifiers.
     */
    public function withoutIdentifiers(): static
    {
        return $this->state(fn () => [
            'gtin13' => null,
            'identifier_exists' => false,
        ]);
    }
}
