<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\MenuCategoryController;
use App\Http\Controllers\RoleController;

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
        Route::get('/debug', [CompanyController::class, 'debug']);
        Route::get('/debug-current-company', [CompanyController::class, 'debugCurrentCompany']);
        Route::post('/force-set', [CompanyController::class, 'forceSetCompany']);
        Route::post('/test-session', [CompanyController::class, 'testSession']);
        Route::get('/current', [CompanyController::class, 'current']);
        Route::get('/user-companies', [CompanyController::class, 'userCompanies']);
        Route::post('/switch', [CompanyController::class, 'switchCompany']);
        Route::post('/switch-by-slug', [CompanyController::class, 'switchCompanyBySlug']);
        Route::get('/development-list', [CompanyController::class, 'getCompaniesForDevelopment']);
        Route::put('/settings', [CompanyController::class, 'updateSettings']);
        Route::get('/analytics', [CompanyController::class, 'analytics']);
    });

    // Role Management Routes (require admin permission)
    Route::prefix('roles')->middleware('permission:manage_roles')->group(function () {
        Route::resource('/', RoleController::class);

        // User role assignment
        Route::post('/assign', [RoleController::class, 'assignToUser']);
        Route::post('/remove', [RoleController::class, 'removeFromUser']);
        Route::get('/users/with-roles', [RoleController::class, 'usersWithRoles']);

        // Available permissions
        Route::get('/permissions/all', [RoleController::class, 'permissions']);
    });

    // Standard CRUD routes - all automatically scoped to current company
    Route::apiResource('companies', CompanyController::class);
    Route::get('menu-categories/debug', [MenuCategoryController::class, 'debug']);
    Route::apiResource('menu-categories', MenuCategoryController::class);
    Route::apiResource('menu-items', MenuItemController::class);

    // Rutas adicionales para categorías jerárquicas
    Route::prefix('menu-categories')->group(function () {
        Route::get('/tree', [MenuCategoryController::class, 'getHierarchicalTree']);
        Route::get('/main', [MenuCategoryController::class, 'getMainCategories']);
        Route::get('/{categoryId}/subcategories', [MenuCategoryController::class, 'getSubcategories']);
        Route::get('/{categoryId}/all-items', [MenuCategoryController::class, 'getAllItemsIncludingSubcategories']);
    });
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
| Ejemplos de uso jerárquico:
|
| GET /api/menu-categories/tree
| - Returns hierarchical tree of all categories with subcategories
|
| GET /api/menu-categories/main
| - Returns only main categories (parent_id = null)
|
| GET /api/menu-categories?parent_id=5
| - Returns subcategories of category with id 5
|
| GET /api/menu-categories?main_only=true
| - Returns only main categories using filter
|
| GET /api/menu-categories?subcategories_only=true&active=true
| - Returns only active subcategories
|
| GET /api/menu-categories/5/subcategories
| - Returns subcategories of category 5
|
| GET /api/menu-categories/5/all-items
| - Returns all menu items from category 5 and its subcategories
|
| POST /api/menu-categories
| - Body: {"name": "Licores", "parent_id": 3, "company_id": 1}
| - Creates subcategory "Licores" under category 3 (e.g., "Bebidas")
|
| GET /api/public/companies
| - Returns all companies (no auth required)
|
| GET /api/public/companies/123/menu
| - Returns public menu for company 123
|
*/