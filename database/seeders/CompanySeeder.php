<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Company::create([
            'name' => 'Compañía de Prueba',
            'address' => 'Calle Falsa 123',
            'owner_id' => 1, // Asumiendo que el usuario de prueba tiene id 1
        ]);
    }
}
