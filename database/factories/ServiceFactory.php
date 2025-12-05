<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'category' => fake()->randomElement(['residential', 'commercial']),
            'price' => fake()->numberBetween(50, 500),
            'duration' => fake()->randomElement(['1h', '2h', '3h']),
            'description' => fake()->paragraph(),
            'image' => fake()->imageUrl(),
        ];
    }
}
