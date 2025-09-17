<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have the necessary data
        $company = Company::first();
        if (!$company) {
            $company = Company::factory()->create([
                'name' => 'Test Restaurant',
                'slug' => 'test-restaurant',
                'email' => 'admin@testrestaurant.com',
                'phone' => '+1234567890',
                'address' => '123 Test Street',
                'is_active' => true,
            ]);
        }

        // Get or create admin user
        $adminUser = User::where('email', 'admin@testrestaurant.com')->first();
        if (!$adminUser) {
            $adminUser = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@testrestaurant.com',
                'is_active' => true,
            ]);
        }

        // Create additional test users if they don't exist
        $managerUser = User::where('email', 'manager@testrestaurant.com')->first();
        if (!$managerUser) {
            $managerUser = User::factory()->create([
                'name' => 'Manager User',
                'email' => 'manager@testrestaurant.com',
                'is_active' => true,
            ]);
        }

        $employeeUser = User::where('email', 'employee@testrestaurant.com')->first();
        if (!$employeeUser) {
            $employeeUser = User::factory()->create([
                'name' => 'Employee User',
                'email' => 'employee@testrestaurant.com',
                'is_active' => true,
            ]);
        }

        // Get roles
        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $employeeRole = Role::where('name', 'employee')->first();

        if (!$adminRole || !$managerRole || !$employeeRole) {
            $this->command->error('Roles not found. Please run RoleSeeder first.');
            return;
        }

        // Clear existing user roles for this company
        DB::table('user_roles')->where('company_id', $company->id)->delete();

        // Assign roles to users in the company
        $adminUser->roles()->attach($adminRole->id, ['company_id' => $company->id]);
        $managerUser->roles()->attach($managerRole->id, ['company_id' => $company->id]);
        $employeeUser->roles()->attach($employeeRole->id, ['company_id' => $company->id]);

        // Add the admin user to the company
        if (!$adminUser->companies()->where('company_id', $company->id)->exists()) {
            $adminUser->companies()->attach($company->id);
        }

        // Add other users to the company
        if (!$managerUser->companies()->where('company_id', $company->id)->exists()) {
            $managerUser->companies()->attach($company->id);
        }

        if (!$employeeUser->companies()->where('company_id', $company->id)->exists()) {
            $employeeUser->companies()->attach($company->id);
        }

        $this->command->info("User roles seeded successfully!");
        $this->command->info("Company: {$company->name}");
        $this->command->info("Admin: {$adminUser->email} -> admin role");
        $this->command->info("Manager: {$managerUser->email} -> manager role");
        $this->command->info("Employee: {$employeeUser->email} -> employee role");
    }
}
