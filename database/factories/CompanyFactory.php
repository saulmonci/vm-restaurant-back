<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'address' => fake()->address(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'logo_url' => fake()->imageUrl(200, 200, 'business'),
            'website' => fake()->url(),
            'timezone' => fake()->timezone(),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'CAD']),
            'language' => fake()->randomElement(['en', 'es', 'fr', 'de']),
            'settings' => [
                'theme' => fake()->randomElement(['blue', 'red', 'green', 'purple']),
                'notifications' => fake()->boolean(),
                'auto_backup' => fake()->boolean(),
            ],
            'business_hours' => json_encode([
                'monday' => ['open' => '09:00', 'close' => '17:00'],
                'tuesday' => ['open' => '09:00', 'close' => '17:00'],
                'wednesday' => ['open' => '09:00', 'close' => '17:00'],
                'thursday' => ['open' => '09:00', 'close' => '17:00'],
                'friday' => ['open' => '09:00', 'close' => '17:00'],
                'saturday' => ['open' => '10:00', 'close' => '15:00'],
                'sunday' => ['closed' => true],
            ]),
            'tax_rate' => fake()->randomFloat(2, 0, 15),
            'business_type' => fake()->randomElement(['restaurant', 'retail', 'service', 'other']),
            'facebook_url' => fake()->optional()->url(),
            'instagram_url' => fake()->optional()->url(),
            'twitter_url' => fake()->optional()->url(),
            'subscription_plan' => fake()->randomElement(['basic', 'premium', 'enterprise']),
            'subscription_expires_at' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'auto_accept_orders' => fake()->boolean(),
            'max_preparation_time' => fake()->numberBetween(15, 60),
            'service_fee_percentage' => fake()->randomFloat(2, 0, 10),
            'owner_id' => null, // Will be set in tests if needed
        ];
    }
}
