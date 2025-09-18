<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;
use App\Models\MenuCategory;
use App\Models\MenuItem;

class HierarchicalMenuSeeder extends Seeder
{
    /**
     * Seed the application's database with hierarchical menu structure.
     */
    public function run(): void
    {
        // Obtener una empresa existente o crear una para el ejemplo
        $company = Company::first();

        if (!$company) {
            $company = Company::create([
                'name' => 'Restaurante Demo',
                'slug' => 'restaurante-demo',
                'email' => 'demo@restaurant.com',
                'phone' => '555-0123',
                'address' => 'Calle Demo 123',
                'is_active' => true,
            ]);
        }

        // Crear estructura jerárquica de categorías
        $this->createHierarchicalCategories($company);
    }

    /**
     * Crear categorías jerárquicas con subcategorías
     */
    private function createHierarchicalCategories(Company $company): void
    {
        // 1. BEBIDAS (categoría principal)
        $bebidas = MenuCategory::create([
            'company_id' => $company->id,
            'name' => 'Bebidas',
            'description' => 'Todas las bebidas disponibles',
            'is_active' => true,
            'sort_order' => 1,
            'parent_id' => null, // Categoría principal
        ]);

        // Subcategorías de Bebidas
        $alcoholicas = MenuCategory::create([
            'company_id' => $company->id,
            'parent_id' => $bebidas->id,
            'name' => 'Bebidas Alcohólicas',
            'description' => 'Cervezas, vinos y licores',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $noAlcoholicas = MenuCategory::create([
            'company_id' => $company->id,
            'parent_id' => $bebidas->id,
            'name' => 'Bebidas Sin Alcohol',
            'description' => 'Refrescos, jugos y aguas',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Sub-subcategorías de Bebidas Alcohólicas
        $licores = MenuCategory::create([
            'company_id' => $company->id,
            'parent_id' => $alcoholicas->id,
            'name' => 'Licores',
            'description' => 'Whisky, Ron, Vodka, etc.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $cervezas = MenuCategory::create([
            'company_id' => $company->id,
            'parent_id' => $alcoholicas->id,
            'name' => 'Cervezas',
            'description' => 'Cervezas nacionales e importadas',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // 2. COMIDA (categoría principal)
        $comida = MenuCategory::create([
            'company_id' => $company->id,
            'name' => 'Comida',
            'description' => 'Platos principales y acompañamientos',
            'is_active' => true,
            'sort_order' => 2,
            'parent_id' => null, // Categoría principal
        ]);

        // Subcategorías de Comida
        $principales = MenuCategory::create([
            'company_id' => $company->id,
            'parent_id' => $comida->id,
            'name' => 'Platos Principales',
            'description' => 'Carnes, pescados y platos vegetarianos',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $acompañamientos = MenuCategory::create([
            'company_id' => $company->id,
            'parent_id' => $comida->id,
            'name' => 'Acompañamientos',
            'description' => 'Guarniciones y extras',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // 3. POSTRES (categoría principal)
        $postres = MenuCategory::create([
            'company_id' => $company->id,
            'name' => 'Postres',
            'description' => 'Dulces y postres caseros',
            'is_active' => true,
            'sort_order' => 3,
            'parent_id' => null, // Categoría principal
        ]);

        // Crear algunos items de ejemplo
        $this->createSampleMenuItems($company, [
            $licores,
            $cervezas,
            $noAlcoholicas,
            $principales,
            $acompañamientos,
            $postres,
        ]);
    }

    /**
     * Crear items de menú de ejemplo
     */
    private function createSampleMenuItems(Company $company, array $categories): void
    {
        $items = [
            // Licores
            [
                'category' => $categories[0],
                'name' => 'Whisky Johnnie Walker Red',
                'price' => 35.00,
                'preparation_time' => 2,
                'spice_level' => 0
            ],
            [
                'category' => $categories[0],
                'name' => 'Ron Bacardí Blanco',
                'price' => 30.00,
                'preparation_time' => 2,
                'spice_level' => 0
            ],
            [
                'category' => $categories[0],
                'name' => 'Vodka Absolut',
                'price' => 40.00,
                'preparation_time' => 2,
                'spice_level' => 0
            ],

            // Cervezas
            [
                'category' => $categories[1],
                'name' => 'Cerveza Pilsen',
                'price' => 5.00,
                'preparation_time' => 1,
                'spice_level' => 0
            ],
            [
                'category' => $categories[1],
                'name' => 'Cerveza Heineken',
                'price' => 7.00,
                'preparation_time' => 1,
                'spice_level' => 0
            ],

            // Bebidas sin alcohol
            [
                'category' => $categories[2],
                'name' => 'Coca Cola',
                'price' => 3.00,
                'preparation_time' => 1,
                'is_vegan' => true
            ],
            [
                'category' => $categories[2],
                'name' => 'Jugo de Naranja Natural',
                'price' => 4.50,
                'preparation_time' => 3,
                'is_vegetarian' => true,
                'is_vegan' => true
            ],
            [
                'category' => $categories[2],
                'name' => 'Agua Mineral',
                'price' => 2.00,
                'preparation_time' => 1,
                'is_vegetarian' => true,
                'is_vegan' => true
            ],

            // Platos principales
            [
                'category' => $categories[3],
                'name' => 'Lomo Saltado',
                'price' => 25.00,
                'preparation_time' => 20,
                'spice_level' => 2,
                'calories' => 650
            ],
            [
                'category' => $categories[3],
                'name' => 'Arroz con Pollo',
                'price' => 18.00,
                'preparation_time' => 25,
                'spice_level' => 1,
                'calories' => 580
            ],
            [
                'category' => $categories[3],
                'name' => 'Ceviche de Pescado',
                'price' => 22.00,
                'preparation_time' => 15,
                'spice_level' => 3,
                'calories' => 320,
                'is_gluten_free' => true
            ],

            // Acompañamientos
            [
                'category' => $categories[4],
                'name' => 'Papas Fritas',
                'price' => 8.00,
                'preparation_time' => 8,
                'calories' => 365,
                'is_vegetarian' => true,
                'is_vegan' => true
            ],
            [
                'category' => $categories[4],
                'name' => 'Arroz Blanco',
                'price' => 5.00,
                'preparation_time' => 15,
                'calories' => 205,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => true
            ],
            [
                'category' => $categories[4],
                'name' => 'Ensalada César',
                'price' => 12.00,
                'preparation_time' => 10,
                'calories' => 280,
                'is_vegetarian' => true
            ],

            // Postres
            [
                'category' => $categories[5],
                'name' => 'Tiramisu',
                'price' => 12.00,
                'preparation_time' => 5,
                'calories' => 450,
                'is_vegetarian' => true
            ],
            [
                'category' => $categories[5],
                'name' => 'Cheesecake de Fresa',
                'price' => 10.00,
                'preparation_time' => 5,
                'calories' => 380,
                'is_vegetarian' => true
            ],
            [
                'category' => $categories[5],
                'name' => 'Helado de Vainilla',
                'price' => 6.00,
                'preparation_time' => 2,
                'calories' => 220,
                'is_vegetarian' => true,
                'is_gluten_free' => true
            ],
        ];

        foreach ($items as $item) {
            // Datos base que siempre existen
            $menuItemData = [
                'company_id' => $company->id,
                'category_id' => $item['category']->id,
                'name' => $item['name'],
                'description' => 'Delicioso ' . strtolower($item['name']),
                'price' => $item['price'],
            ];

            // Agregar campos adicionales solo si existen en la tabla
            if (Schema::hasColumn('menu_items', 'is_available')) {
                $menuItemData['is_available'] = true;
            }

            if (Schema::hasColumn('menu_items', 'preparation_time') && isset($item['preparation_time'])) {
                $menuItemData['preparation_time'] = $item['preparation_time'];
            }

            if (Schema::hasColumn('menu_items', 'spice_level') && isset($item['spice_level'])) {
                $menuItemData['spice_level'] = $item['spice_level'];
            }

            if (Schema::hasColumn('menu_items', 'calories') && isset($item['calories'])) {
                $menuItemData['calories'] = $item['calories'];
            }

            if (Schema::hasColumn('menu_items', 'is_vegetarian') && isset($item['is_vegetarian'])) {
                $menuItemData['is_vegetarian'] = $item['is_vegetarian'];
            }

            if (Schema::hasColumn('menu_items', 'is_vegan') && isset($item['is_vegan'])) {
                $menuItemData['is_vegan'] = $item['is_vegan'];
            }

            if (Schema::hasColumn('menu_items', 'is_gluten_free') && isset($item['is_gluten_free'])) {
                $menuItemData['is_gluten_free'] = $item['is_gluten_free'];
            }

            MenuItem::create($menuItemData);
        }
    }
}
