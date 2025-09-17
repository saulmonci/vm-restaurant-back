<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas API para autenticación y endpoints principales
|
*/

// Rutas de autenticación (públicas)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
});

// Incluir rutas de company (requieren autenticación)
require __DIR__ . '/company.php';

/*
|--------------------------------------------------------------------------
| Flujo de autenticación para app móvil:
|--------------------------------------------------------------------------
|
| 1. POST /api/auth/login
|    Body: {"email": "user@example.com", "password": "password"}
|    Response: {"token": "xxx", "user": {...}, "companies": [...]}
|
| 2. GET /api/company/user-companies
|    Headers: Authorization: Bearer {token}
|    Response: [{"id": 1, "name": "Restaurant A"}, ...]
|
| 3. POST /api/company/switch
|    Headers: Authorization: Bearer {token}
|    Body: {"company_id": 1}
|    Response: {"success": true, "company": {...}}
|
| 4. GET /api/menu-items
|    Headers: Authorization: Bearer {token}
|    Response: [{menu items for current company}]
|
*/