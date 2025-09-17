<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::create([
            'name' => 'Usuario Prueba',
            'email' => 'prueba@example.com',
            'password' => bcrypt('password'),
        ]);

        //assign user to a company
        $company = \App\Models\Company::first();
        if ($company) {
            $company->users()->attach($user->id);
        }
    }
}
