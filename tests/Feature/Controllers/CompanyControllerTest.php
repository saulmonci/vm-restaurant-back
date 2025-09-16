<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class CompanyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $company2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create companies
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'settings' => [
                'theme' => 'blue',
                'currency' => 'USD',
                'timezone' => 'America/New_York'
            ]
        ]);

        $this->company2 = Company::factory()->create([
            'name' => 'Second Company',
            'settings' => [
                'theme' => 'red',
                'currency' => 'EUR'
            ]
        ]);

        // Create user
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'display_name' => 'Johnny',
            'email' => 'john@test.com',
            'timezone' => 'America/Chicago',
            'preferred_language' => 'en',
            'preferred_currency' => 'USD',
            'preferences' => [
                'theme' => 'dark',
                'notifications' => true
            ]
        ]);

        // Associate user with companies
        CompanyUser::create(['user_id' => $this->user->id, 'company_id' => $this->company->id]);
        CompanyUser::create(['user_id' => $this->user->id, 'company_id' => $this->company2->id]);

        // Create some test data for analytics
        $category = MenuCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Category'
        ]);

        MenuItem::factory()->count(5)->create([
            'menu_category_id' => $category->id,
            'available' => true
        ]);

        MenuItem::factory()->count(2)->create([
            'menu_category_id' => $category->id,
            'available' => false
        ]);
    }

    public function test_current_endpoint_returns_company_info()
    {
        $response = $this->actingAs($this->user)->getJson('/api/companies/current');

        $response->assertOk()
            ->assertJsonStructure([
                'company' => [
                    'id',
                    'name',
                ],
                'settings'
            ])
            ->assertJson([
                'company' => [
                    'name' => $this->company->name,
                ],
                'settings' => [
                    'theme' => 'blue',
                    'currency' => 'USD',
                ]
            ]);
    }

    public function test_current_endpoint_fails_without_authentication()
    {
        $response = $this->getJson('/api/companies/current');

        $response->assertUnauthorized();
    }

    public function test_user_companies_endpoint()
    {
        $response = $this->actingAs($this->user)->getJson('/api/companies/user-companies');

        $response->assertOk()
            ->assertJsonStructure([
                'companies' => [
                    '*' => [
                        'id',
                        'name',
                    ]
                ],
                'current_company_id'
            ]);

        $companiesData = $response->json('companies');
        $this->assertCount(2, $companiesData);

        $companyNames = collect($companiesData)->pluck('name')->toArray();
        $this->assertContains('Test Company', $companyNames);
        $this->assertContains('Second Company', $companyNames);
    }

    public function test_switch_company_endpoint_success()
    {
        $response = $this->actingAs($this->user)->putJson('/api/companies/switch', [
            'company_id' => $this->company2->id
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Company switched successfully',
                'company' => [
                    'name' => 'Second Company'
                ]
            ]);
    }

    public function test_switch_company_endpoint_fails_for_unauthorized_company()
    {
        $unauthorizedCompany = Company::factory()->create(['name' => 'Unauthorized']);

        $response = $this->actingAs($this->user)->putJson('/api/companies/switch', [
            'company_id' => $unauthorizedCompany->id
        ]);

        $response->assertForbidden()
            ->assertJson([
                'error' => 'You do not have access to this company'
            ]);
    }

    public function test_switch_company_endpoint_validation()
    {
        $response = $this->actingAs($this->user)->putJson('/api/companies/switch', [
            'company_id' => 'invalid'
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['company_id']);
    }

    public function test_update_settings_endpoint()
    {
        $newSettings = [
            'theme' => 'dark',
            'new_feature' => 'enabled',
            'currency' => 'EUR'
        ];

        $response = $this->actingAs($this->user)->putJson('/api/companies/settings', [
            'settings' => $newSettings
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Settings updated successfully',
                'settings' => [
                    'theme' => 'dark',
                    'new_feature' => 'enabled',
                    'currency' => 'EUR',
                    'timezone' => 'America/New_York' // Should preserve existing
                ]
            ]);
    }

    public function test_update_settings_validation()
    {
        $response = $this->actingAs($this->user)->putJson('/api/companies/settings', [
            'invalid' => 'data'
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['settings']);
    }

    public function test_analytics_endpoint()
    {
        $response = $this->actingAs($this->user)->getJson('/api/companies/analytics');

        $response->assertOk()
            ->assertJsonStructure([
                'company_id',
                'period',
                'user_info' => [
                    'user_id',
                    'user_name',
                    'user_timezone',
                    'is_admin'
                ],
                'metrics' => [
                    'total_menu_items',
                    'total_categories',
                    'active_items',
                    'last_updated'
                ]
            ]);

        $analytics = $response->json();

        $this->assertEquals($this->company->id, $analytics['company_id']);
        $this->assertEquals($this->user->id, $analytics['user_info']['user_id']);
        $this->assertEquals('Johnny', $analytics['user_info']['user_name']);
        $this->assertEquals('America/Chicago', $analytics['user_info']['user_timezone']);
        $this->assertEquals(7, $analytics['metrics']['total_menu_items']); // 5 + 2
        $this->assertEquals(1, $analytics['metrics']['total_categories']);
        $this->assertEquals(1, $analytics['metrics']['active_items']); // Categories with active items
    }

    public function test_analytics_endpoint_with_period_parameter()
    {
        $response = $this->actingAs($this->user)->getJson('/api/companies/analytics?period=7days');

        $response->assertOk()
            ->assertJson([
                'period' => '7days'
            ]);
    }

    public function test_user_profile_endpoint()
    {
        $response = $this->actingAs($this->user)->getJson('/api/companies/user-profile');

        $response->assertOk()
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'timezone',
                    'language',
                    'currency',
                    'is_admin',
                    'is_active',
                    'preferences'
                ],
                'company' => [
                    'id',
                    'name',
                    'settings'
                ],
                'companies_access'
            ]);

        $profile = $response->json();

        $this->assertEquals($this->user->id, $profile['user']['id']);
        $this->assertEquals('Johnny', $profile['user']['name']);
        $this->assertEquals('john@test.com', $profile['user']['email']);
        $this->assertEquals('America/Chicago', $profile['user']['timezone']);
        $this->assertEquals('dark', $profile['user']['preferences']['theme']);
        $this->assertEquals(2, $profile['companies_access']);
    }

    public function test_user_profile_endpoint_without_authentication()
    {
        $response = $this->getJson('/api/companies/user-profile');

        $response->assertUnauthorized()
            ->assertJson([
                'error' => 'User not authenticated'
            ]);
    }

    public function test_update_user_preferences_endpoint()
    {
        $newPreferences = [
            'theme' => 'light',
            'dashboard_layout' => 'list',
            'notifications' => false
        ];

        $response = $this->actingAs($this->user)->putJson('/api/companies/user-preferences', [
            'preferences' => $newPreferences
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'User preferences updated successfully',
                'preferences' => [
                    'theme' => 'light',
                    'dashboard_layout' => 'list',
                    'notifications' => false
                ]
            ]);
    }

    public function test_update_user_preferences_validation()
    {
        $response = $this->actingAs($this->user)->putJson('/api/companies/user-preferences', [
            'invalid' => 'data'
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['preferences']);
    }

    public function test_endpoints_fail_when_user_has_no_company_access()
    {
        $userWithoutCompany = User::factory()->create([
            'name' => 'No Company User',
            'email' => 'nocompany@test.com'
        ]);

        // Current endpoint should fail
        $response = $this->actingAs($userWithoutCompany)->getJson('/api/companies/current');
        $response->assertNotFound()
            ->assertJson(['error' => 'No company context found']);

        // Analytics should fail
        $response = $this->actingAs($userWithoutCompany)->getJson('/api/companies/analytics');
        $response->assertNotFound()
            ->assertJson(['error' => 'No company context found']);

        // But user profile should work (it handles no company case)
        $response = $this->actingAs($userWithoutCompany)->getJson('/api/companies/user-profile');
        $response->assertOk();

        $profile = $response->json();
        $this->assertEquals(0, $profile['companies_access']);
        $this->assertNull($profile['company']['id']);
    }

    public function test_middleware_applies_company_scoping()
    {
        // This test verifies that the CompanyScopedMiddleware is working
        // by checking that only the current company's data is accessible

        $this->actingAs($this->user);

        // Create data for company2 that shouldn't be accessible
        $company2Category = MenuCategory::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Company 2 Category'
        ]);

        // Switch to company1 context
        $this->putJson('/api/companies/switch', ['company_id' => $this->company->id]);

        // Analytics should only show company1 data
        $response = $this->getJson('/api/companies/analytics');
        $analytics = $response->json();

        $this->assertEquals($this->company->id, $analytics['company_id']);
        $this->assertEquals(1, $analytics['metrics']['total_categories']); // Only company1's category
    }
}
