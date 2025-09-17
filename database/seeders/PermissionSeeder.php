<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Gestión de Compañías
            [
                'name' => 'company.view',
                'display_name' => 'Ver Compañía',
                'description' => 'Puede ver información de la compañía',
                'module' => 'company',
                'action' => 'view',
                'is_system_permission' => true,
            ],
            [
                'name' => 'company.edit',
                'display_name' => 'Editar Compañía',
                'description' => 'Puede editar información de la compañía',
                'module' => 'company',
                'action' => 'edit',
                'is_system_permission' => true,
            ],
            [
                'name' => 'company.settings',
                'display_name' => 'Configurar Compañía',
                'description' => 'Puede cambiar configuraciones de la compañía',
                'module' => 'company',
                'action' => 'settings',
                'is_system_permission' => true,
            ],

            // Gestión de Usuarios
            [
                'name' => 'users.view',
                'display_name' => 'Ver Usuarios',
                'description' => 'Puede ver lista de usuarios',
                'module' => 'users',
                'action' => 'view',
                'is_system_permission' => true,
            ],
            [
                'name' => 'users.create',
                'display_name' => 'Crear Usuarios',
                'description' => 'Puede crear nuevos usuarios',
                'module' => 'users',
                'action' => 'create',
                'is_system_permission' => true,
            ],
            [
                'name' => 'users.edit',
                'display_name' => 'Editar Usuarios',
                'description' => 'Puede editar información de usuarios',
                'module' => 'users',
                'action' => 'edit',
                'is_system_permission' => true,
            ],
            [
                'name' => 'users.delete',
                'display_name' => 'Eliminar Usuarios',
                'description' => 'Puede eliminar usuarios',
                'module' => 'users',
                'action' => 'delete',
                'is_system_permission' => true,
            ],

            // Gestión de Roles
            [
                'name' => 'roles.view',
                'display_name' => 'Ver Roles',
                'description' => 'Puede ver roles y permisos',
                'module' => 'roles',
                'action' => 'view',
                'is_system_permission' => true,
            ],
            [
                'name' => 'roles.create',
                'display_name' => 'Crear Roles',
                'description' => 'Puede crear nuevos roles',
                'module' => 'roles',
                'action' => 'create',
                'is_system_permission' => true,
            ],
            [
                'name' => 'roles.edit',
                'display_name' => 'Editar Roles',
                'description' => 'Puede editar roles y asignar permisos',
                'module' => 'roles',
                'action' => 'edit',
                'is_system_permission' => true,
            ],
            [
                'name' => 'roles.delete',
                'display_name' => 'Eliminar Roles',
                'description' => 'Puede eliminar roles',
                'module' => 'roles',
                'action' => 'delete',
                'is_system_permission' => true,
            ],
            [
                'name' => 'roles.assign',
                'display_name' => 'Asignar Roles',
                'description' => 'Puede asignar roles a usuarios',
                'module' => 'roles',
                'action' => 'assign',
                'is_system_permission' => true,
            ],

            // Gestión de Menú
            [
                'name' => 'menu.view',
                'display_name' => 'Ver Menú',
                'description' => 'Puede ver el menú y sus items',
                'module' => 'menu',
                'action' => 'view',
                'is_system_permission' => true,
            ],
            [
                'name' => 'menu.create',
                'display_name' => 'Crear Items de Menú',
                'description' => 'Puede crear nuevos items y categorías',
                'module' => 'menu',
                'action' => 'create',
                'is_system_permission' => true,
            ],
            [
                'name' => 'menu.edit',
                'display_name' => 'Editar Menú',
                'description' => 'Puede editar items y categorías del menú',
                'module' => 'menu',
                'action' => 'edit',
                'is_system_permission' => true,
            ],
            [
                'name' => 'menu.delete',
                'display_name' => 'Eliminar Items de Menú',
                'description' => 'Puede eliminar items y categorías',
                'module' => 'menu',
                'action' => 'delete',
                'is_system_permission' => true,
            ],
            [
                'name' => 'menu.manage',
                'display_name' => 'Gestionar Menú',
                'description' => 'Control total sobre el menú',
                'module' => 'menu',
                'action' => 'manage',
                'is_system_permission' => true,
            ],

            // Reportes y Analíticas
            [
                'name' => 'reports.view',
                'display_name' => 'Ver Reportes',
                'description' => 'Puede ver reportes básicos',
                'module' => 'reports',
                'action' => 'view',
                'is_system_permission' => true,
            ],
            [
                'name' => 'reports.advanced',
                'display_name' => 'Reportes Avanzados',
                'description' => 'Puede ver reportes avanzados y analíticas',
                'module' => 'reports',
                'action' => 'advanced',
                'is_system_permission' => true,
            ],
            [
                'name' => 'reports.export',
                'display_name' => 'Exportar Reportes',
                'description' => 'Puede exportar reportes a Excel/PDF',
                'module' => 'reports',
                'action' => 'export',
                'is_system_permission' => true,
            ],

            // Órdenes (para futuro)
            [
                'name' => 'orders.view',
                'display_name' => 'Ver Órdenes',
                'description' => 'Puede ver órdenes',
                'module' => 'orders',
                'action' => 'view',
                'is_system_permission' => true,
            ],
            [
                'name' => 'orders.create',
                'display_name' => 'Crear Órdenes',
                'description' => 'Puede crear nuevas órdenes',
                'module' => 'orders',
                'action' => 'create',
                'is_system_permission' => true,
            ],
            [
                'name' => 'orders.edit',
                'display_name' => 'Editar Órdenes',
                'description' => 'Puede editar órdenes',
                'module' => 'orders',
                'action' => 'edit',
                'is_system_permission' => true,
            ],
            [
                'name' => 'orders.manage',
                'display_name' => 'Gestionar Órdenes',
                'description' => 'Control total sobre órdenes',
                'module' => 'orders',
                'action' => 'manage',
                'is_system_permission' => true,
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::createIfNotExists($permissionData);
        }

        $this->command->info('Permisos del sistema creados exitosamente.');
    }
}
