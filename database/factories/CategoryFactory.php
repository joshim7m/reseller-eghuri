<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'slug' => null,
            'parent_id' => null,
            'image_path' => null,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function child(): static
    {
        return $this->state(fn () => [
            'parent_id' => Category::factory(),
        ]);
    }
}
