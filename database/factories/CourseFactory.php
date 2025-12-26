<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'subtitle' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'total_duration' => fake()->numberBetween(30, 600), // duration in minutes
            'price' => fake()->randomFloat(2, 10, 200),
        ];
    }
}
