<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => 'processing',
            'subtotal' => 50.00,
            'discount_amount' => 0,
            'shipping_amount' => 5.00,
            'tax_amount' => 3.00,
            'total' => 58.00,
            'etsy_receipt_id' => null,
            'shipping_method' => 'Standard',
            'shipping_first_name' => fake()->firstName(),
            'shipping_last_name' => fake()->lastName(),
            'shipping_line1' => fake()->streetAddress(),
            'shipping_city' => fake()->city(),
            'shipping_state' => fake()->stateAbbr(),
            'shipping_zip' => fake()->postcode(),
            'shipping_country' => 'US',
            'billing_first_name' => fake()->firstName(),
            'billing_last_name' => fake()->lastName(),
            'billing_line1' => fake()->streetAddress(),
            'billing_city' => fake()->city(),
            'billing_state' => fake()->stateAbbr(),
            'billing_zip' => fake()->postcode(),
            'billing_country' => 'US',
        ];
    }
}
