<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Facades\CurrentCompany;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $company = CurrentCompany::get();

        if (!$company) {
            return response()->json(['message' => 'No hay contexto de compañía'], 403);
        }

        // Verificar si el usuario tiene alguno de los roles requeridos
        if (!$this->userHasAnyRole($user, $company->id, $roles)) {
            return response()->json([
                'message' => 'No tienes el rol necesario para realizar esta acción',
                'required_roles' => $roles
            ], 403);
        }

        return $next($request);
    }

    /**
     * Verificar si el usuario tiene alguno de los roles especificados en una compañía
     */
    private function userHasAnyRole($user, int $companyId, array $roleNames): bool
    {
        // Obtener los roles del usuario en la compañía específica
        $userRoleNames = $user->roles()
            ->where('company_id', $companyId)
            ->pluck('name')
            ->toArray();

        // Verificar si el usuario tiene alguno de los roles requeridos
        return !empty(array_intersect($userRoleNames, $roleNames));
    }
}
