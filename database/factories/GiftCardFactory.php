<?php

namespace Database\Factories;

use App\Models\GiftCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftCard>
 */
class GiftCardFactory extends Factory
{
    protected $model = GiftCard::class;

    public function definition(): array
    {
        $balance = $this->faker->randomFloat(2, 10, 100);

        return [
            'code' => GiftCard::generateCode(),
            'initial_balance' => $balance,
            'balance' => $balance,
            'currency' => 'USD',
            'active' => true,
            'expires_at' => null,
        ];
    }

    public function balance(float $amount): static
    {
        return $this->state(fn () => [
            'initial_balance' => $amount,
            'balance' => $amount,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }
}
