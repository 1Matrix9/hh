<?php

namespace Database\Factories;

use App\Models\CourseSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Video>
 */
class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => CourseSection::factory(),
            'title' => fake()->sentence(),
            'video_url' => fake()->url(),
            'duration' => fake()->numberBetween(5, 60), // duration in minutes
            'order_index' => 1,
        ];
    }
}
