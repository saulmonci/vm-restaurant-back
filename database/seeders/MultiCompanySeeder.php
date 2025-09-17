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

class MultiCompanySeeder extends Seeder
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
            'phone' => '+34 912 345 678',
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

        // Create users for this company
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

        $managerUser = User::create([
            'name' => 'Marco Bianchi',
            'email' => 'marco@pizzeriaitaliana.com',
            'password' => Hash::make('password'),
            'display_name' => 'Marco',
            'is_active' => true,
            'preferred_language' => 'es',
            'timezone' => 'Europe/Madrid',
            'preferred_currency' => 'EUR',
        ]);

        $employeeUser = User::create([
            'name' => 'Sofia Martinez',
            'email' => 'sofia@pizzeriaitaliana.com',
            'password' => Hash::make('password'),
            'display_name' => 'Sofia',
            'is_active' => true,
            'preferred_language' => 'es',
            'timezone' => 'Europe/Madrid',
            'preferred_currency' => 'EUR',
        ]);

        // Assign users to company
        $adminUser->companies()->attach($company->id);
        $managerUser->companies()->attach($company->id);
        $employeeUser->companies()->attach($company->id);

        // Set admin user as company owner
        $company->update(['owner_id' => $adminUser->id]);

        // Assign roles
        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $employeeRole = Role::where('name', 'employee')->first();

        $adminUser->roles()->attach($adminRole->id, ['company_id' => $company->id]);
        $managerUser->roles()->attach($managerRole->id, ['company_id' => $company->id]);
        $employeeUser->roles()->attach($employeeRole->id, ['company_id' => $company->id]);

        // Create menu categories for Pizza Restaurant
        $pizzaCategory = MenuCategory::create([
            'company_id' => $company->id,
            'name' => 'Pizzas Tradicionales',
        ]);

        $pastaCategory = MenuCategory::create([
            'company_id' => $company->id,
            'name' => 'Pastas Caseras',
        ]);

        $drinkCategory = MenuCategory::create([
            'company_id' => $company->id,
            'name' => 'Bebidas',
        ]);

        // Create menu items for pizzas
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
            'category_id' => $pizzaCategory->id,
            'name' => 'Pizza Diavola',
            'description' => 'Tomate, mozzarella, salami picante, aceite de chile',
            'price' => 14.20,
        ]);
        ]);

        // Create pasta items
        MenuItem::create([
            'company_id' => $company->id,
            'menu_category_id' => $pastaCategory->id,
            'name' => 'Spaghetti Carbonara',
            'description' => 'Pasta fresca con panceta, huevo, parmesano, pimienta negra',
            'price' => 13.80,
            'preparation_time' => 12,
            'is_available' => true,
            'is_featured' => true,
            'allergens' => ['gluten', 'eggs', 'lactose'],
            'nutritional_info' => ['calories' => 420, 'protein' => 20, 'carbs' => 45],
        ]);

        MenuItem::create([
            'company_id' => $company->id,
            'menu_category_id' => $pastaCategory->id,
            'name' => 'Fettuccine Alfredo',
            'description' => 'Pasta fresca con salsa de mantequilla, parmesano y nata',
            'price' => 12.90,
            'preparation_time' => 10,
            'is_available' => true,
            'is_featured' => false,
            'allergens' => ['gluten', 'lactose'],
            'nutritional_info' => ['calories' => 380, 'protein' => 15, 'carbs' => 42],
        ]);

        // Create drinks
        MenuItem::create([
            'company_id' => $company->id,
            'menu_category_id' => $drinkCategory->id,
            'name' => 'Chianti Classico',
            'description' => 'Vino tinto italiano DOCG',
            'price' => 18.50,
            'preparation_time' => 2,
            'is_available' => true,
            'is_featured' => true,
            'allergens' => ['sulfites'],
            'nutritional_info' => ['calories' => 125, 'alcohol' => 13.5],
        ]);

        $this->command->info("✓ Created Pizzería Italiana with {$company->menuItems()->count()} menu items");
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
            'phone' => '+1 555 123 4567',
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

        // Create users for this company
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

        $managerUser = User::create([
            'name' => 'Michael Brown',
            'email' => 'michael@cafecentral.com',
            'password' => Hash::make('password'),
            'display_name' => 'Mike',
            'is_active' => true,
            'preferred_language' => 'en',
            'timezone' => 'America/New_York',
            'preferred_currency' => 'USD',
        ]);

        $baristaUser = User::create([
            'name' => 'Sarah Davis',
            'email' => 'sarah@cafecentral.com',
            'password' => Hash::make('password'),
            'display_name' => 'Sarah',
            'is_active' => true,
            'preferred_language' => 'en',
            'timezone' => 'America/New_York',
            'preferred_currency' => 'USD',
        ]);

        // Assign users to company
        $adminUser->companies()->attach($company->id);
        $managerUser->companies()->attach($company->id);
        $baristaUser->companies()->attach($company->id);

        // Set admin user as company owner
        $company->update(['owner_id' => $adminUser->id]);

        // Assign roles
        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $employeeRole = Role::where('name', 'employee')->first();

        $adminUser->roles()->attach($adminRole->id, ['company_id' => $company->id]);
        $managerUser->roles()->attach($managerRole->id, ['company_id' => $company->id]);
        $baristaUser->roles()->attach($employeeRole->id, ['company_id' => $company->id]);

        // Create menu categories for Cafe
        $coffeeCategory = MenuCategory::create([
            'company_id' => $company->id,
            'name' => 'Specialty Coffee',
        ]);

        $pastriesCategory = MenuCategory::create([
            'company_id' => $company->id,
            'name' => 'Fresh Pastries',
        ]);

        $sandwichCategory = MenuCategory::create([
            'company_id' => $company->id,
            'name' => 'Sandwiches & Light Meals',
        ]);

        // Create coffee items
        MenuItem::create([
            'company_id' => $company->id,
            'menu_category_id' => $coffeeCategory->id,
            'name' => 'Ethiopian Single Origin',
            'description' => 'Light roast with floral notes and citrus finish',
            'price' => 4.50,
            'preparation_time' => 3,
            'is_available' => true,
            'is_featured' => true,
            'allergens' => ['caffeine'],
            'nutritional_info' => ['calories' => 5, 'caffeine' => 120],
        ]);

        MenuItem::create([
            'company_id' => $company->id,
            'menu_category_id' => $coffeeCategory->id,
            'name' => 'House Blend Espresso',
            'description' => 'Our signature dark roast blend',
            'price' => 3.25,
            'preparation_time' => 2,
            'is_available' => true,
            'is_featured' => true,
            'allergens' => ['caffeine'],
            'nutritional_info' => ['calories' => 3, 'caffeine' => 80],
        ]);

        MenuItem::create([
            'company_id' => $company->id,
            'menu_category_id' => $coffeeCategory->id,
            'name' => 'Vanilla Latte',
            'description' => 'Espresso with steamed milk and vanilla syrup',
            'price' => 5.75,
            'preparation_time' => 4,
            'is_available' => true,
            'is_featured' => false,
            'allergens' => ['caffeine', 'lactose'],
            'nutritional_info' => ['calories' => 190, 'caffeine' => 75],
        ]);

        // Create pastry items
        MenuItem::create([
            'company_id' => $company->id,
            'menu_category_id' => $pastriesCategory->id,
            'name' => 'Almond Croissant',
            'description' => 'Buttery croissant with almond cream and sliced almonds',
            'price' => 3.95,
            'preparation_time' => 1,
            'is_available' => true,
            'is_featured' => true,
            'allergens' => ['gluten', 'nuts', 'lactose', 'eggs'],
            'nutritional_info' => ['calories' => 320, 'protein' => 8, 'carbs' => 28],
        ]);

        MenuItem::create([
            'company_id' => $company->id,
            'menu_category_id' => $pastriesCategory->id,
            'name' => 'Blueberry Muffin',
            'description' => 'Fresh baked muffin with wild blueberries',
            'price' => 2.85,
            'preparation_time' => 1,
            'is_available' => true,
            'is_featured' => false,
            'allergens' => ['gluten', 'eggs', 'lactose'],
            'nutritional_info' => ['calories' => 280, 'protein' => 5, 'carbs' => 42],
        ]);

        // Create sandwich items
        MenuItem::create([
            'company_id' => $company->id,
            'menu_category_id' => $sandwichCategory->id,
            'name' => 'Avocado Toast',
            'description' => 'Sourdough bread with smashed avocado, lime, and sea salt',
            'price' => 8.50,
            'preparation_time' => 5,
            'is_available' => true,
            'is_featured' => true,
            'allergens' => ['gluten'],
            'nutritional_info' => ['calories' => 240, 'protein' => 6, 'carbs' => 32],
        ]);

        MenuItem::create([
            'company_id' => $company->id,
            'menu_category_id' => $sandwichCategory->id,
            'name' => 'Turkey Club Sandwich',
            'description' => 'Roasted turkey, bacon, lettuce, tomato on toasted bread',
            'price' => 12.95,
            'preparation_time' => 7,
            'is_available' => true,
            'is_featured' => false,
            'allergens' => ['gluten'],
            'nutritional_info' => ['calories' => 480, 'protein' => 32, 'carbs' => 38],
        ]);

        $this->command->info("✓ Created Café Central with {$company->menuItems()->count()} menu items");
    }
}
