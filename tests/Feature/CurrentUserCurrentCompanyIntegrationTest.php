<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Facades\CurrentUser;
use App\Facades\CurrentCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class CurrentUserCurrentCompanyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company1;
    protected $company2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create companies
        $this->company1 = Company::factory()->create([
            'name' => 'Alpha Corp',
            'settings' => ['theme' => 'blue', 'currency' => 'USD']
        ]);

        $this->company2 = Company::factory()->create([
            'name' => 'Beta Inc',
            'settings' => ['theme' => 'red', 'currency' => 'EUR']
        ]);

        // Create user with preferences
        $this->user = User::factory()->create([
            'name' => 'John Manager',
            'display_name' => 'John',
            'email' => 'john@manager.com',
            'timezone' => 'America/New_York',
            'preferred_language' => 'en',
            'preferred_currency' => 'USD',
            'preferences' => [
                'dashboard_layout' => 'grid',
                'notifications' => true
            ]
        ]);

        // Associate user with both companies
        CompanyUser::create(['user_id' => $this->user->id, 'company_id' => $this->company1->id]);
        CompanyUser::create(['user_id' => $this->user->id, 'company_id' => $this->company2->id]);
    }

    public function test_both_facades_work_together_when_authenticated()
    {
        Auth::login($this->user);

        // Both facades should return data
        $this->assertNotNull(CurrentUser::id());
        $this->assertNotNull(CurrentCompany::id());

        // User data should be correct
        $this->assertEquals($this->user->id, CurrentUser::id());
        $this->assertEquals('John', CurrentUser::name());
        $this->assertEquals('john@manager.com', CurrentUser::email());

        // Company data should be from one of user's companies
        $companyId = CurrentCompany::id();
        $this->assertTrue(in_array($companyId, [$this->company1->id, $this->company2->id]));

        // User should have access to companies
        $userCompanies = CurrentUser::companies();
        $this->assertCount(2, $userCompanies);
    }

    public function test_both_facades_return_null_when_not_authenticated()
    {
        Auth::logout();

        $this->assertNull(CurrentUser::id());
        $this->assertNull(CurrentUser::get());
        $this->assertFalse(CurrentUser::exists());

        $this->assertNull(CurrentCompany::id());
        $this->assertNull(CurrentCompany::get());
        $this->assertFalse(CurrentCompany::exists());
    }

    public function test_user_company_switching_scenario()
    {
        Auth::login($this->user);

        // Get initial state
        $initialUserId = CurrentUser::id();
        $initialCompanyId = CurrentCompany::id();

        $this->assertEquals($this->user->id, $initialUserId);
        $this->assertNotNull($initialCompanyId);

        // Switch to company1
        $switchSuccess = CurrentCompany::switchTo($this->company1->id);
        $this->assertTrue($switchSuccess);

        // User should remain the same, company should change
        $this->assertEquals($this->user->id, CurrentUser::id());
        $this->assertEquals($this->company1->id, CurrentCompany::id());
        $this->assertEquals('Alpha Corp', CurrentCompany::get()->name);

        // Switch to company2
        $switchSuccess = CurrentCompany::switchTo($this->company2->id);
        $this->assertTrue($switchSuccess);

        // User still same, company changed again
        $this->assertEquals($this->user->id, CurrentUser::id());
        $this->assertEquals($this->company2->id, CurrentCompany::id());
        $this->assertEquals('Beta Inc', CurrentCompany::get()->name);
    }

    public function test_user_without_company_access()
    {
        // Create user without company access
        $userWithoutCompany = User::factory()->create([
            'name' => 'No Company User',
            'email' => 'nocompany@test.com'
        ]);

        Auth::login($userWithoutCompany);

        // User should be available
        $this->assertEquals($userWithoutCompany->id, CurrentUser::id());
        $this->assertEquals('No Company User', CurrentUser::name());
        $this->assertTrue(CurrentUser::exists());

        // But no company access
        $this->assertNull(CurrentCompany::id());
        $this->assertNull(CurrentCompany::get());
        $this->assertFalse(CurrentCompany::exists());

        // User companies should be empty
        $this->assertCount(0, CurrentUser::companies());
    }

    public function test_combined_user_and_company_data_for_dashboard()
    {
        Auth::login($this->user);

        // Simulate dashboard data gathering
        $dashboardData = [
            'user' => [
                'id' => CurrentUser::id(),
                'name' => CurrentUser::name(),
                'email' => CurrentUser::email(),
                'timezone' => CurrentUser::timezone(),
                'preferences' => CurrentUser::preferences(),
                'companies_count' => CurrentUser::companies()->count(),
            ],
            'company' => [
                'id' => CurrentCompany::id(),
                'name' => CurrentCompany::get()?->name,
                'settings' => CurrentCompany::settings(),
            ],
            'navigation' => [
                'available_companies' => CurrentUser::companies()->pluck('name', 'id'),
                'current_company_name' => CurrentCompany::get()?->name,
            ]
        ];

        // Verify all data is present
        $this->assertEquals($this->user->id, $dashboardData['user']['id']);
        $this->assertEquals('John', $dashboardData['user']['name']);
        $this->assertEquals(2, $dashboardData['user']['companies_count']);
        $this->assertEquals('grid', $dashboardData['user']['preferences']['dashboard_layout']);

        $this->assertNotNull($dashboardData['company']['id']);
        $this->assertContains($dashboardData['company']['name'], ['Alpha Corp', 'Beta Inc']);

        $this->assertCount(2, $dashboardData['navigation']['available_companies']);
    }

    public function test_preference_updates_work_independently()
    {
        Auth::login($this->user);

        // Update user preferences
        $userUpdateSuccess = CurrentUser::updatePreferences([
            'theme' => 'dark',
            'language' => 'es'
        ]);

        // Update company settings
        $companyUpdateSuccess = CurrentCompany::updateSettings([
            'company_theme' => 'corporate',
            'timezone' => 'UTC'
        ]);

        $this->assertTrue($userUpdateSuccess);
        $this->assertTrue($companyUpdateSuccess);

        // Verify updates
        $this->assertEquals('dark', CurrentUser::preferences('theme'));
        $this->assertEquals('es', CurrentUser::preferences('language'));

        $this->assertEquals('corporate', CurrentCompany::settings('company_theme'));
        $this->assertEquals('UTC', CurrentCompany::settings('timezone'));

        // Original preferences should be preserved
        $this->assertTrue(CurrentUser::preferences('notifications'));
    }

    public function test_cache_independence()
    {
        Auth::login($this->user);

        // Load both facades
        $userId = CurrentUser::id();
        $companyId = CurrentCompany::id();

        $this->assertNotNull($userId);
        $this->assertNotNull($companyId);

        // Clear user cache only
        CurrentUser::clearCache();

        // User data should reload, company should remain cached
        $this->assertEquals($userId, CurrentUser::id());
        $this->assertEquals($companyId, CurrentCompany::id());

        // Clear company cache only
        CurrentCompany::clearCache();

        // Both should still work (reload as needed)
        $this->assertEquals($userId, CurrentUser::id());
        $this->assertEquals($companyId, CurrentCompany::id());
    }

    public function test_refresh_both_facades()
    {
        Auth::login($this->user);

        // Load initial data
        $initialUserName = CurrentUser::name();
        $initialCompanyName = CurrentCompany::get()->name;

        // Make external changes to database
        $this->user->update(['display_name' => 'Updated John']);
        CurrentCompany::get()->update(['name' => 'Updated Company Name']);

        // Data should still be cached (old values)
        $this->assertEquals($initialUserName, CurrentUser::name());
        $this->assertEquals($initialCompanyName, CurrentCompany::get()->name);

        // Refresh both
        CurrentUser::refresh();
        CurrentCompany::clearCache();

        // Should now have updated values
        $this->assertEquals('Updated John', CurrentUser::name());
        $this->assertEquals('Updated Company Name', CurrentCompany::get()->name);
    }

    public function test_error_scenarios()
    {
        Auth::login($this->user);

        // Try to switch to non-existent company
        $invalidSwitch = CurrentCompany::switchTo(99999);
        $this->assertFalse($invalidSwitch);

        // User should still be valid
        $this->assertTrue(CurrentUser::exists());

        // Company should remain unchanged
        $originalCompanyId = CurrentCompany::id();
        $this->assertEquals($originalCompanyId, CurrentCompany::id());

        // Try to update preferences for non-existent user
        Auth::logout();

        $invalidUpdate = CurrentUser::updatePreferences(['test' => 'value']);
        $this->assertFalse($invalidUpdate);
    }
}
