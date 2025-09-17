<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Facades\CurrentCompany;

class RoleController extends Controller
{
    /**
     * Listar todos los roles de la compañía actual
     */
    public function index(): JsonResponse
    {
        $company = CurrentCompany::get();

        if (!$company) {
            return response()->json(['message' => 'No hay contexto de compañía'], 400);
        }

        $roles = Role::where('company_id', $company->id)
            ->with('permissions')
            ->get();

        return response()->json($roles);
    }

    /**
     * Mostrar un rol específico
     */
    public function show(Role $role): JsonResponse
    {
        $company = CurrentCompany::get();

        // Verificar que el rol pertenece a la compañía actual
        if ($role->company_id !== $company->id) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        $role->load('permissions');

        return response()->json($role);
    }

    /**
     * Crear un nuevo rol
     */
    public function store(Request $request): JsonResponse
    {
        $company = CurrentCompany::get();

        if (!$company) {
            return response()->json(['message' => 'No hay contexto de compañía'], 400);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        // Verificar que el nombre del rol sea único en la compañía
        $existingRole = Role::where('company_id', $company->id)
            ->where('name', $request->name)
            ->first();

        if ($existingRole) {
            return response()->json([
                'message' => 'Ya existe un rol con ese nombre en esta compañía'
            ], 422);
        }

        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description,
            'company_id' => $company->id
        ]);

        // Asignar permisos si se proporcionaron
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        $role->load('permissions');

        return response()->json($role, 201);
    }

    /**
     * Actualizar un rol existente
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $company = CurrentCompany::get();

        // Verificar que el rol pertenece a la compañía actual
        if ($role->company_id !== $company->id) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        // Verificar unicidad del nombre (excluyendo el rol actual)
        $existingRole = Role::where('company_id', $company->id)
            ->where('name', $request->name)
            ->where('id', '!=', $role->id)
            ->first();

        if ($existingRole) {
            return response()->json([
                'message' => 'Ya existe un rol con ese nombre en esta compañía'
            ], 422);
        }

        $role->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        // Actualizar permisos
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        $role->load('permissions');

        return response()->json($role);
    }

    /**
     * Eliminar un rol
     */
    public function destroy(Role $role): JsonResponse
    {
        $company = CurrentCompany::get();

        // Verificar que el rol pertenece a la compañía actual
        if ($role->company_id !== $company->id) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        // Verificar que no hay usuarios asignados a este rol
        $usersCount = $role->users()->count();

        if ($usersCount > 0) {
            return response()->json([
                'message' => "No se puede eliminar el rol. Hay {$usersCount} usuario(s) asignado(s) a este rol."
            ], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Rol eliminado exitosamente']);
    }

    /**
     * Asignar rol a un usuario
     */
    public function assignToUser(Request $request): JsonResponse
    {
        $company = CurrentCompany::get();

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        $user = User::findOrFail($request->user_id);
        $role = Role::findOrFail($request->role_id);

        // Verificar que el rol pertenece a la compañía actual
        if ($role->company_id !== $company->id) {
            return response()->json(['message' => 'Rol no válido para esta compañía'], 422);
        }

        // Verificar que el usuario pertenece a la compañía
        if (!$user->companies()->where('companies.id', $company->id)->exists()) {
            return response()->json(['message' => 'El usuario no pertenece a esta compañía'], 422);
        }

        // Asignar el rol
        $success = $user->assignRole($role->name, $company->id);

        if ($success) {
            return response()->json(['message' => 'Rol asignado exitosamente']);
        } else {
            return response()->json(['message' => 'Error al asignar el rol'], 500);
        }
    }

    /**
     * Remover rol de un usuario
     */
    public function removeFromUser(Request $request): JsonResponse
    {
        $company = CurrentCompany::get();

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        $user = User::findOrFail($request->user_id);
        $role = Role::findOrFail($request->role_id);

        // Verificar que el rol pertenece a la compañía actual
        if ($role->company_id !== $company->id) {
            return response()->json(['message' => 'Rol no válido para esta compañía'], 422);
        }

        // Remover el rol
        $success = $user->removeRole($role->name, $company->id);

        if ($success) {
            return response()->json(['message' => 'Rol removido exitosamente']);
        } else {
            return response()->json(['message' => 'Error al remover el rol'], 500);
        }
    }

    /**
     * Listar usuarios con sus roles en la compañía actual
     */
    public function usersWithRoles(): JsonResponse
    {
        $company = CurrentCompany::get();

        if (!$company) {
            return response()->json(['message' => 'No hay contexto de compañía'], 400);
        }

        $users = User::whereHas('companies', function ($query) use ($company) {
            $query->where('companies.id', $company->id);
        })
            ->with(['roles' => function ($query) use ($company) {
                $query->wherePivot('company_id', $company->id);
            }])
            ->get();

        return response()->json($users);
    }

    /**
     * Listar todos los permisos disponibles
     */
    public function permissions(): JsonResponse
    {
        $permissions = Permission::all(['id', 'name', 'description', 'category']);

        return response()->json($permissions);
    }
}
