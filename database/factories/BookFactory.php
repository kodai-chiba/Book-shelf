<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
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
            'author' => fake()->name(),
            'isbn' => fake()->unique()->numerify('#############'),
            'published_date' => fake()->date(),
            'description' => fake()->text(200),
            'image_url' => fake()->imageUrl(),
            'created_by' => User::factory(),
        ];
    }
}
