<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\Role;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SimpleMultiCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating multiple companies with their own data...');

        // Create two additional companies
        $this->createPizzaRestaurant();
        $this->createCafeCompany();

        $this->command->info('Multi-company data seeded successfully!');
    }

    /**
     * Create Pizza Restaurant with its data
     */
    private function createPizzaRestaurant(): void
    {
        // Check if the company already exists
        $existingCompany = Company::where('slug', 'pizzeria-italiana')->first();
        if ($existingCompany) {
            $this->command->info('Pizzería Italiana already exists, skipping...');
            return;
        }

        // Create company
        $company = Company::create([
            'name' => 'Pizzería Italiana',
            'slug' => 'pizzeria-italiana',
            'email' => 'admin@pizzeriaitaliana.com',
            'address' => 'Calle Mayor 123, Madrid, España',
            'city' => 'Madrid',
            'country' => 'España',
            'website' => 'https://pizzeriaitaliana.com',
            'description' => 'Auténtica pizzería italiana con recetas tradicionales',
            'is_active' => true,
            'settings' => [
                'currency' => 'EUR',
                'language' => 'es',
                'timezone' => 'Europe/Madrid',
                'tax_rate' => 21.0
            ]
        ]);

        // Create admin user
        $adminUser = User::create([
            'name' => 'Giuseppe Rossi',
            'email' => 'giuseppe@pizzeriaitaliana.com',
            'password' => Hash::make('password'),
            'display_name' => 'Giuseppe',
            'is_active' => true,
            'preferred_language' => 'es',
            'timezone' => 'Europe/Madrid',
            'preferred_currency' => 'EUR',
        ]);

        // Set admin user as company owner
        $company->update(['owner_id' => $adminUser->id]);

        // Assign user to company
        $adminUser->companies()->attach($company->id);

        // Create menu categories
        $pizzaCategory = MenuCategory::create([
            'company_id' => $company->id,
            'name' => 'Pizzas Tradicionales',
        ]);

        $pastaCategory = MenuCategory::create([
            'company_id' => $company->id,
            'name' => 'Pastas Caseras',
        ]);

        // Create menu items (only using existing fields)
        MenuItem::create([
            'company_id' => $company->id,
            'category_id' => $pizzaCategory->id,
            'name' => 'Pizza Margherita',
            'description' => 'Tomate, mozzarella di bufala, albahaca fresca, aceite de oliva',
            'price' => 12.50,
        ]);

        MenuItem::create([
            'company_id' => $company->id,
            'category_id' => $pizzaCategory->id,
            'name' => 'Pizza Quattro Stagioni',
            'description' => 'Tomate, mozzarella, jamón, champiñones, alcachofas, aceitunas',
            'price' => 15.90,
        ]);

        MenuItem::create([
            'company_id' => $company->id,
            'category_id' => $pastaCategory->id,
            'name' => 'Spaghetti Carbonara',
            'description' => 'Pasta fresca con panceta, huevo, parmesano y pimienta negra',
            'price' => 11.80,
        ]);

        $this->command->info('✓ Created Pizzería Italiana with menu items');
    }

    /**
     * Create Cafe Company with its data
     */
    private function createCafeCompany(): void
    {
        // Check if the company already exists
        $existingCompany = Company::where('slug', 'cafe-central')->first();
        if ($existingCompany) {
            $this->command->info('Café Central already exists, skipping...');
            return;
        }

        // Create company
        $company = Company::create([
            'name' => 'Café Central',
            'slug' => 'cafe-central',
            'email' => 'info@cafecentral.com',
            'address' => '456 Broadway, New York, NY 10013',
            'city' => 'New York',
            'country' => 'USA',
            'website' => 'https://cafecentral.com',
            'description' => 'Café especializado en granos selectos y repostería artesanal',
            'is_active' => true,
            'settings' => [
                'currency' => 'USD',
                'language' => 'en',
                'timezone' => 'America/New_York',
                'tax_rate' => 8.25
            ]
        ]);

        // Create admin user
        $adminUser = User::create([
            'name' => 'Emily Johnson',
            'email' => 'emily@cafecentral.com',
            'password' => Hash::make('password'),
            'display_name' => 'Emily',
            'is_active' => true,
            'preferred_language' => 'en',
            'timezone' => 'America/New_York',
            'preferred_currency' => 'USD',
        ]);

        // Set admin user as company owner
        $company->update(['owner_id' => $adminUser->id]);

        // Assign user to company
        $adminUser->companies()->attach($company->id);

        // Create menu categories
        $coffeeCategory = MenuCategory::create([
            'company_id' => $company->id,
            'name' => 'Specialty Coffee',
        ]);

        $pastriesCategory = MenuCategory::create([
            'company_id' => $company->id,
            'name' => 'Fresh Pastries',
        ]);

        // Create menu items
        MenuItem::create([
            'company_id' => $company->id,
            'category_id' => $coffeeCategory->id,
            'name' => 'Ethiopian Single Origin',
            'description' => 'Light roast with floral notes and citrus finish',
            'price' => 4.50,
        ]);

        MenuItem::create([
            'company_id' => $company->id,
            'category_id' => $coffeeCategory->id,
            'name' => 'House Blend Espresso',
            'description' => 'Rich and balanced with chocolate undertones',
            'price' => 3.25,
        ]);

        MenuItem::create([
            'company_id' => $company->id,
            'category_id' => $pastriesCategory->id,
            'name' => 'Croissant',
            'description' => 'Buttery, flaky pastry baked fresh daily',
            'price' => 2.75,
        ]);

        $this->command->info('✓ Created Café Central with menu items');
    }
}
