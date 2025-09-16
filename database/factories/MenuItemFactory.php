<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuItem>
 */
class MenuItemFactory extends Factory
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
                'Caesar Salad',
                'Grilled Chicken',
                'Beef Burger',
                'Fish & Chips',
                'Pasta Carbonara',
                'Margherita Pizza',
                'Chocolate Cake',
                'Coffee',
                'Orange Juice',
                'Tomato Soup',
                'Garlic Bread',
                'Ice Cream'
            ]),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomFloat(2, 5, 50),
            'available' => fake()->boolean(85), // 85% chance of being available
            'preparation_time' => fake()->numberBetween(5, 45),
            'image_url' => fake()->optional()->imageUrl(300, 200, 'food'),
            'ingredients' => fake()->optional()->words(5, true),
            'allergens' => fake()->optional()->randomElement(['nuts', 'dairy', 'gluten', 'shellfish']),
            'calories' => fake()->optional()->numberBetween(100, 800),
            'menu_category_id' => null, // Will be set in tests
        ];
    }
}
