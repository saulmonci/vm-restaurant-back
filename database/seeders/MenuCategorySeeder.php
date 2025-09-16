<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = \App\Models\Company::first();
        if ($company) {
            \App\Models\MenuCategory::create([
                'company_id' => $company->id,
                'name' => 'Bebidas',
            ]);
            \App\Models\MenuCategory::create([
                'company_id' => $company->id,
                'name' => 'Comida',
            ]);
        }
    }
}
