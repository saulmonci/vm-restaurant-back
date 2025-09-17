<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'manage_users',
                'create_menu',
                'edit_menu',
                'delete_menu',
                'view_reports',
                'manage_settings',
                'view_orders',
                'process_orders'
            ]),
            'display_name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'module' => $this->faker->randomElement(['menu', 'users', 'orders', 'reports', 'settings']),
            'action' => $this->faker->randomElement(['view', 'create', 'edit', 'delete', 'manage']),
            'is_system_permission' => false,
            'metadata' => null,
        ];
    }
}
