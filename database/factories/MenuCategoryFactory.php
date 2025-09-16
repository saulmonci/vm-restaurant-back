<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuCategory>
 */
class MenuCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Appetizers',
                'Main Courses',
                'Desserts',
                'Beverages',
                'Salads',
                'Soups',
                'Pasta',
                'Pizza',
                'Burgers',
                'Seafood'
            ]),
            'description' => fake()->optional()->sentence(),
            'display_order' => fake()->numberBetween(1, 10),
            'is_active' => fake()->boolean(90), // 90% chance of being active
            'company_id' => null, // Will be set in tests
        ];
    }
}
