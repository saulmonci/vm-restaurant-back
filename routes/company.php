<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\MenuCategoryController;

/*
|--------------------------------------------------------------------------
| API Routes - Company Context
|--------------------------------------------------------------------------
|
| Routes para manejo de contexto de empresa y multi-tenancy
|
*/

Route::middleware(['auth:sanctum', 'company.scoped'])->group(function () {

    // Company Context Routes
    Route::prefix('company')->group(function () {
        Route::get('/current', [CompanyController::class, 'current']);
        Route::get('/user-companies', [CompanyController::class, 'userCompanies']);
        Route::post('/switch', [CompanyController::class, 'switchCompany']);
        Route::put('/settings', [CompanyController::class, 'updateSettings']);
        Route::get('/analytics', [CompanyController::class, 'analytics']);
    });

    // Standard CRUD routes - all automatically scoped to current company
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('menu-categories', MenuCategoryController::class);
    Route::apiResource('menu-items', MenuItemController::class);
});

/*
|--------------------------------------------------------------------------
| API Routes - Public (no company context required)
|--------------------------------------------------------------------------
|
| Routes que no requieren contexto de empresa
|
*/

Route::prefix('public')->group(function () {

    // Public company listing (for marketplace, etc.)
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{id}', [CompanyController::class, 'show']);

    // Public menu browsing
    Route::get('/companies/{companyId}/menu', function ($companyId) {
        // Return public menu for a specific company
        return app(MenuItemController::class)->index(request()->merge(['company_id' => $companyId]));
    });
});

/*
|--------------------------------------------------------------------------
| Usage Examples
|--------------------------------------------------------------------------
|
| GET /api/company/current
| - Returns current company info and settings
|
| GET /api/company/user-companies  
| - Returns all companies user has access to
|
| POST /api/company/switch
| - Body: {"company_id": 123}
| - Switches context to different company
|
| GET /api/menu-items?paginate=true&available=true
| - Returns menu items for current company only (auto-scoped)
|
| GET /api/company/analytics?period=7days
| - Returns analytics for current company
|
| PUT /api/company/settings
| - Body: {"settings": {"theme": "dark", "currency": "USD"}}
| - Updates company-specific settings
|
| GET /api/public/companies
| - Returns all companies (no auth required)
|
| GET /api/public/companies/123/menu
| - Returns public menu for company 123
|
*/