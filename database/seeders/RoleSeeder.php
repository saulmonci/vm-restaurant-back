<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Definir roles del sistema con sus permisos
        $systemRoles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrador',
                'description' => 'Acceso completo a todo el sistema',
                'is_system_role' => true,
                'company_id' => null,
                'permissions' => ['*'] // Todos los permisos
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrador',
                'description' => 'Administrador de compañía con acceso completo',
                'is_system_role' => true,
                'company_id' => null,
                'permissions' => [
                    'company.view',
                    'company.edit',
                    'company.settings',
                    'users.view',
                    'users.create',
                    'users.edit',
                    'users.delete',
                    'roles.view',
                    'roles.create',
                    'roles.edit',
                    'roles.assign',
                    'menu.view',
                    'menu.create',
                    'menu.edit',
                    'menu.delete',
                    'menu.manage',
                    'reports.view',
                    'reports.advanced',
                    'reports.export',
                    'orders.view',
                    'orders.create',
                    'orders.edit',
                    'orders.manage',
                ]
            ],
            [
                'name' => 'manager',
                'display_name' => 'Gerente',
                'description' => 'Gerente con permisos de gestión operativa',
                'is_system_role' => true,
                'company_id' => null,
                'permissions' => [
                    'company.view',
                    'users.view',
                    'users.create',
                    'users.edit',
                    'roles.view',
                    'roles.assign',
                    'menu.view',
                    'menu.create',
                    'menu.edit',
                    'menu.manage',
                    'reports.view',
                    'reports.advanced',
                    'orders.view',
                    'orders.create',
                    'orders.edit',
                    'orders.manage',
                ]
            ],
            [
                'name' => 'employee',
                'display_name' => 'Empleado',
                'description' => 'Empleado con permisos operativos básicos',
                'is_system_role' => true,
                'company_id' => null,
                'permissions' => [
                    'company.view',
                    'menu.view',
                    'menu.create',
                    'menu.edit',
                    'orders.view',
                    'orders.create',
                    'orders.edit',
                ]
            ],
            [
                'name' => 'viewer',
                'display_name' => 'Visualizador',
                'description' => 'Solo puede ver información, sin modificar',
                'is_system_role' => true,
                'company_id' => null,
                'permissions' => [
                    'company.view',
                    'users.view',
                    'menu.view',
                    'reports.view',
                    'orders.view',
                ]
            ],
            [
                'name' => 'cashier',
                'display_name' => 'Cajero',
                'description' => 'Especializado en manejo de órdenes y pagos',
                'is_system_role' => true,
                'company_id' => null,
                'permissions' => [
                    'company.view',
                    'menu.view',
                    'orders.view',
                    'orders.create',
                    'orders.edit',
                ]
            ],
            [
                'name' => 'chef',
                'display_name' => 'Chef',
                'description' => 'Especializado en gestión de menú y órdenes de cocina',
                'is_system_role' => true,
                'company_id' => null,
                'permissions' => [
                    'company.view',
                    'menu.view',
                    'menu.create',
                    'menu.edit',
                    'menu.manage',
                    'orders.view',
                    'orders.edit',
                ]
            ],
        ];

        foreach ($systemRoles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            // Crear o actualizar el rol
            $role = Role::updateOrCreate(
                [
                    'name' => $roleData['name'],
                    'company_id' => $roleData['company_id']
                ],
                $roleData
            );

            // Asignar permisos
            if (in_array('*', $permissions)) {
                // Asignar todos los permisos
                $allPermissions = Permission::all();
                $role->permissions()->sync($allPermissions->pluck('id'));
            } else {
                // Asignar permisos específicos
                $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
                $role->permissions()->sync($permissionIds);
            }

            $this->command->info("Rol '{$role->display_name}' creado con " . count($permissions) . " permisos.");
        }

        $this->command->info('Roles del sistema creados exitosamente.');
    }
}
