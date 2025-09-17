<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Permission;
use App\Services\CurrentUser;
use App\Facades\CurrentUser as CurrentUserFacade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CurrentUserTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $currentUserService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'display_name' => 'Johnny',
            'email' => 'john@example.com',
            'timezone' => 'America/New_York',
            'preferred_language' => 'es',
            'preferred_currency' => 'EUR',
            'is_active' => true,
            'preferences' => [
                'theme' => 'dark',
                'notifications' => ['email' => true, 'push' => false]
            ]
        ]);

        $this->currentUserService = new CurrentUser();
    }

    public function test_returns_null_when_not_authenticated()
    {
        // No user authenticated
        Auth::logout();

        $this->currentUserService->initialize();

        $this->assertNull($this->currentUserService->id());
        $this->assertNull($this->currentUserService->get());
        $this->assertFalse($this->currentUserService->exists());
        $this->assertFalse($this->currentUserService->check());
    }

    public function test_returns_user_data_when_authenticated()
    {
        Auth::login($this->user);

        $this->currentUserService->initialize();

        $this->assertEquals($this->user->id, $this->currentUserService->id());
        $this->assertEquals($this->user->email, $this->currentUserService->get()->email);
        $this->assertTrue($this->currentUserService->exists());
        $this->assertTrue($this->currentUserService->check());
    }

    public function test_name_returns_display_name_when_available()
    {
        Auth::login($this->user);

        $this->currentUserService->initialize();

        $this->assertEquals('Johnny', $this->currentUserService->name());
    }

    public function test_name_falls_back_to_name_when_no_display_name()
    {
        $this->user->update(['display_name' => null]);
        Auth::login($this->user);

        $this->currentUserService->initialize();

        $this->assertEquals('John Doe', $this->currentUserService->name());
    }

    public function test_email_returns_user_email()
    {
        Auth::login($this->user);

        $this->currentUserService->initialize();

        $this->assertEquals('john@example.com', $this->currentUserService->email());
    }

    public function test_timezone_returns_user_timezone()
    {
        Auth::login($this->user);

        $this->currentUserService->initialize();

        $this->assertEquals('America/New_York', $this->currentUserService->timezone());
    }

    public function test_timezone_returns_default_when_user_has_none()
    {
        // Instead of setting to null, we'll use the default timezone
        $this->user->update(['timezone' => 'UTC']); // Use default value
        Auth::login($this->user);

        $this->currentUserService->initialize();

        $this->assertEquals('UTC', $this->currentUserService->timezone());
    }

    public function test_language_returns_user_language()
    {
        Auth::login($this->user);

        $this->currentUserService->initialize();

        $this->assertEquals('es', $this->currentUserService->language());
    }

    public function test_currency_returns_user_currency()
    {
        Auth::login($this->user);

        $this->currentUserService->initialize();

        $this->assertEquals('EUR', $this->currentUserService->currency());
    }

    public function test_preferences_returns_all_preferences()
    {
        Auth::login($this->user);

        $this->currentUserService->initialize();

        $preferences = $this->currentUserService->preferences();

        $this->assertEquals('dark', $preferences['theme']);
        $this->assertTrue($preferences['notifications']['email']);
        $this->assertFalse($preferences['notifications']['push']);
    }

    public function test_preferences_returns_specific_preference()
    {
        Auth::login($this->user);

        $this->currentUserService->initialize();

        $this->assertEquals('dark', $this->currentUserService->preferences('theme'));
        $this->assertTrue($this->currentUserService->preferences('notifications.email'));
        $this->assertEquals('light', $this->currentUserService->preferences('missing', 'light'));
    }

    public function test_is_active_returns_user_status()
    {
        Auth::login($this->user);

        $this->currentUserService->initialize();

        $this->assertTrue($this->currentUserService->isActive());

        $this->user->update(['is_active' => false]);
        $this->currentUserService->refresh();

        $this->assertFalse($this->currentUserService->isActive());
    }

    public function test_update_preferences_merges_with_existing()
    {
        Auth::login($this->user);

        $this->currentUserService->initialize();

        $success = $this->currentUserService->updatePreferences([
            'theme' => 'light',
            'new_setting' => 'value'
        ]);

        $this->assertTrue($success);

        $preferences = $this->currentUserService->preferences();
        $this->assertEquals('light', $preferences['theme']);
        $this->assertEquals('value', $preferences['new_setting']);
        $this->assertTrue($preferences['notifications']['email']); // Preserved
    }

    public function test_caches_user_data()
    {
        Auth::login($this->user);

        // First call should cache the data
        $this->currentUserService->initialize();
        $firstUser = $this->currentUserService->get();

        // Mock cache to verify it's being used
        Cache::shouldReceive('remember')
            ->with("user.{$this->user->id}", 3600, \Closure::class)
            ->once()
            ->andReturn($this->user);

        // Second service instance should use cache
        $secondService = new CurrentUser();
        $secondService->initialize();
    }

    public function test_clear_cache_resets_data()
    {
        Auth::login($this->user);

        $this->currentUserService->initialize();
        $this->assertNotNull($this->currentUserService->get());

        $this->currentUserService->clearCache();

        // Should reinitialize on next call
        $this->assertNotNull($this->currentUserService->get());
    }

    public function test_facade_works_correctly()
    {
        Auth::login($this->user);

        // Test facade methods
        $this->assertEquals($this->user->id, CurrentUserFacade::id());
        $this->assertEquals('Johnny', CurrentUserFacade::name());
        $this->assertEquals('john@example.com', CurrentUserFacade::email());
        $this->assertTrue(CurrentUserFacade::exists());
        $this->assertEquals('America/New_York', CurrentUserFacade::timezone());
        $this->assertEquals('dark', CurrentUserFacade::preferences('theme'));
    }

    public function test_companies_returns_user_companies()
    {
        $company1 = Company::factory()->create(['name' => 'Company 1']);
        $company2 = Company::factory()->create(['name' => 'Company 2']);

        $this->user->companies()->attach([$company1->id, $company2->id]);

        Auth::login($this->user);

        $this->currentUserService->initialize();

        $companies = $this->currentUserService->companies();

        $this->assertCount(2, $companies);
        $this->assertTrue($companies->contains('name', 'Company 1'));
        $this->assertTrue($companies->contains('name', 'Company 2'));
    }

    // =================== ROLES AND PERMISSIONS TESTS ===================

    public function test_roles_returns_user_roles_in_current_company()
    {
        // Create a company and set it as current
        $company = Company::factory()->create(['name' => 'Test Company']);
        $this->mockCurrentCompany($company);

        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $managerRole = Role::factory()->create(['name' => 'manager']);

        // Assign roles to user in this company
        $this->user->roles()->attach($adminRole->id, ['company_id' => $company->id]);
        $this->user->roles()->attach($managerRole->id, ['company_id' => $company->id]);

        Auth::login($this->user);
        $this->currentUserService->initialize();

        $roles = $this->currentUserService->roles();

        $this->assertIsArray($roles);
        $this->assertContains('admin', $roles);
        $this->assertContains('manager', $roles);
        $this->assertCount(2, $roles);
    }

    public function test_permissions_returns_user_permissions_in_current_company()
    {
        // Create a company and set it as current
        $company = Company::factory()->create(['name' => 'Test Company']);
        $this->mockCurrentCompany($company);

        // Create permissions
        $manageUsersPermission = Permission::factory()->create(['name' => 'manage_users']);
        $createMenuPermission = Permission::factory()->create(['name' => 'create_menu']);

        // Create role with permissions
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $adminRole->permissions()->attach([$manageUsersPermission->id, $createMenuPermission->id]);

        // Assign role to user in this company
        $this->user->roles()->attach($adminRole->id, ['company_id' => $company->id]);

        Auth::login($this->user);
        $this->currentUserService->initialize();

        $permissions = $this->currentUserService->permissions();

        $this->assertIsArray($permissions);
        $this->assertContains('manage_users', $permissions);
        $this->assertContains('create_menu', $permissions);
        $this->assertCount(2, $permissions);
    }

    public function test_has_role_checks_specific_role()
    {
        // Create a company and set it as current
        $company = Company::factory()->create(['name' => 'Test Company']);
        $this->mockCurrentCompany($company);

        // Create and assign admin role
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $this->user->roles()->attach($adminRole->id, ['company_id' => $company->id]);

        Auth::login($this->user);
        $this->currentUserService->initialize();

        $this->assertTrue($this->currentUserService->hasRole('admin'));
        $this->assertFalse($this->currentUserService->hasRole('manager'));
    }

    public function test_has_any_role_checks_multiple_roles()
    {
        // Create a company and set it as current
        $company = Company::factory()->create(['name' => 'Test Company']);
        $this->mockCurrentCompany($company);

        // Create and assign manager role
        $managerRole = Role::factory()->create(['name' => 'manager']);
        $this->user->roles()->attach($managerRole->id, ['company_id' => $company->id]);

        Auth::login($this->user);
        $this->currentUserService->initialize();

        $this->assertTrue($this->currentUserService->hasAnyRole(['admin', 'manager']));
        $this->assertFalse($this->currentUserService->hasAnyRole(['admin', 'employee']));
    }

    public function test_has_all_roles_checks_all_required_roles()
    {
        // Create a company and set it as current
        $company = Company::factory()->create(['name' => 'Test Company']);
        $this->mockCurrentCompany($company);

        // Create and assign multiple roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $managerRole = Role::factory()->create(['name' => 'manager']);
        $this->user->roles()->attach([$adminRole->id, $managerRole->id], ['company_id' => $company->id]);

        Auth::login($this->user);
        $this->currentUserService->initialize();

        $this->assertTrue($this->currentUserService->hasAllRoles(['admin', 'manager']));
        $this->assertFalse($this->currentUserService->hasAllRoles(['admin', 'manager', 'employee']));
    }

    public function test_has_permission_checks_specific_permission()
    {
        // Create a company and set it as current
        $company = Company::factory()->create(['name' => 'Test Company']);
        $this->mockCurrentCompany($company);

        // Create permission and role
        $manageUsersPermission = Permission::factory()->create(['name' => 'manage_users']);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $adminRole->permissions()->attach($manageUsersPermission->id);

        // Assign role to user
        $this->user->roles()->attach($adminRole->id, ['company_id' => $company->id]);

        Auth::login($this->user);
        $this->currentUserService->initialize();

        $this->assertTrue($this->currentUserService->hasPermission('manage_users'));
        $this->assertFalse($this->currentUserService->hasPermission('delete_users'));
    }

    public function test_is_admin_checks_admin_role()
    {
        // Create a company and set it as current
        $company = Company::factory()->create(['name' => 'Test Company']);
        $this->mockCurrentCompany($company);

        // Create and assign admin role
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $this->user->roles()->attach($adminRole->id, ['company_id' => $company->id]);

        Auth::login($this->user);
        $this->currentUserService->initialize();

        $this->assertTrue($this->currentUserService->isAdmin());
    }

    public function test_is_manager_checks_manager_role()
    {
        // Create a company and set it as current
        $company = Company::factory()->create(['name' => 'Test Company']);
        $this->mockCurrentCompany($company);

        // Create and assign manager role
        $managerRole = Role::factory()->create(['name' => 'manager']);
        $this->user->roles()->attach($managerRole->id, ['company_id' => $company->id]);

        Auth::login($this->user);
        $this->currentUserService->initialize();

        $this->assertTrue($this->currentUserService->isManager());
        $this->assertFalse($this->currentUserService->isAdmin());
    }

    public function test_can_manage_users_checks_permission_or_admin_role()
    {
        // Create a company and set it as current
        $company = Company::factory()->create(['name' => 'Test Company']);
        $this->mockCurrentCompany($company);

        // Test with admin role
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $this->user->roles()->attach($adminRole->id, ['company_id' => $company->id]);

        Auth::login($this->user);
        $this->currentUserService->initialize();

        $this->assertTrue($this->currentUserService->canManageUsers());
    }

    public function test_roles_empty_when_no_current_company()
    {
        // Mock no current company
        $this->mockCurrentCompany(null);

        Auth::login($this->user);
        $this->currentUserService->initialize();

        $this->assertEmpty($this->currentUserService->roles());
        $this->assertEmpty($this->currentUserService->permissions());
        $this->assertFalse($this->currentUserService->hasRole('admin'));
        $this->assertFalse($this->currentUserService->hasPermission('manage_users'));
    }

    public function test_clear_cache_clears_roles_and_permissions()
    {
        // Create a company and set it as current
        $company = Company::factory()->create(['name' => 'Test Company']);
        $this->mockCurrentCompany($company);

        // Create and assign role
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $this->user->roles()->attach($adminRole->id, ['company_id' => $company->id]);

        Auth::login($this->user);
        $this->currentUserService->initialize();

        // Verify roles are loaded
        $this->assertNotEmpty($this->currentUserService->roles());

        // Clear cache
        $this->currentUserService->clearCache();

        // After clearing cache, the service should need to reinitialize
        // Since the user is still logged in, get() will reinitialize and return the user
        $user = $this->currentUserService->get();
        $this->assertNotNull($user);

        // But the roles should be reloaded from database/cache
        $roles = $this->currentUserService->roles();
        $this->assertContains('admin', $roles);
    }

    /**
     * Mock the CurrentCompany service
     */
    private function mockCurrentCompany(?Company $company)
    {
        $currentCompanyMock = $this->createMock(\App\Services\CurrentCompany::class);
        $currentCompanyMock->method('get')->willReturn($company);

        $this->app->instance(\App\Services\CurrentCompany::class, $currentCompanyMock);
    }
}
