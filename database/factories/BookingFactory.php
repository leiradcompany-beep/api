<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'service_id' => \App\Models\Service::factory(),
            'cleaner_id' => \App\Models\User::factory(),
            'date' => fake()->date(),
            'time' => fake()->time(),
            'duration' => '2h',
            'address' => fake()->address(),
            'price' => fake()->numberBetween(50, 500),
            'status' => 'pending',
        ];
    }
}
