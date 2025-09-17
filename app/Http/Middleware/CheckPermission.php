<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Facades\CurrentCompany;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $company = CurrentCompany::get();

        if (!$company) {
            return response()->json(['message' => 'No hay contexto de compañía'], 403);
        }

        // Verificar si el usuario tiene el permiso en la compañía actual
        if (!$this->userHasPermission($user, $company->id, $permission)) {
            return response()->json([
                'message' => 'No tienes permisos para realizar esta acción',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }

    /**
     * Verificar si el usuario tiene un permiso específico en una compañía
     */
    private function userHasPermission($user, int $companyId, string $permissionName): bool
    {
        // Obtener los roles del usuario en la compañía específica
        $userRoles = $user->roles()
            ->where('company_id', $companyId)
            ->with('permissions')
            ->get();

        // Verificar si alguno de los roles tiene el permiso requerido
        foreach ($userRoles as $role) {
            if ($role->permissions->contains('name', $permissionName)) {
                return true;
            }
        }

        return false;
    }
}
