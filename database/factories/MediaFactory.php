<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::slug(fake()->words(2, true)).'-'.fake()->unique()->randomNumber(5);

        return [
            'filename' => "{$name}.png",
            'original_name' => "{$name}.png",
            'disk' => config('filesystems.default'),
            'path' => "media/{$name}.png",
            'mime_type' => 'image/png',
            'size_bytes' => fake()->numberBetween(1000, 500000),
            'alt_text' => fake()->optional()->sentence(),
            'uploaded_by' => null,
        ];
    }
}
