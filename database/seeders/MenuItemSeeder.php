<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = \App\Models\Company::first();
        if (!$company) return;

        $bebidas = \App\Models\MenuCategory::where('name', 'Bebidas')->where('company_id', $company->id)->first();
        $comida = \App\Models\MenuCategory::where('name', 'Comida')->where('company_id', $company->id)->first();

        if ($bebidas) {
            \App\Models\MenuItem::create([
                'company_id' => $company->id,
                'category_id' => $bebidas->id,
                'name' => 'Coca Cola',
                'description' => 'Refresco clásico',
                'price' => 20.00,
            ]);
            \App\Models\MenuItem::create([
                'company_id' => $company->id,
                'category_id' => $bebidas->id,
                'name' => 'Agua',
                'description' => 'Agua natural',
                'price' => 15.00,
            ]);
        }

        if ($comida) {
            \App\Models\MenuItem::create([
                'company_id' => $company->id,
                'category_id' => $comida->id,
                'name' => 'Hamburguesa',
                'description' => 'Hamburguesa con queso',
                'price' => 50.00,
            ]);
            \App\Models\MenuItem::create([
                'company_id' => $company->id,
                'category_id' => $comida->id,
                'name' => 'Papas Fritas',
                'description' => 'Papas fritas crujientes',
                'price' => 25.00,
            ]);
        }
    }
}
