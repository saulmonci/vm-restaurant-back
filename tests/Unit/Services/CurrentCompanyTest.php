<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Services\CurrentCompany;
use App\Facades\CurrentCompany as CurrentCompanyFacade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CurrentCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $currentCompanyService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'settings' => [
                'theme' => 'blue',
                'timezone' => 'America/Chicago',
                'features' => ['analytics', 'reporting']
            ]
        ]);

        // Create test user
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        // Associate user with company
        CompanyUser::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id
        ]);

        $this->currentCompanyService = new CurrentCompany();
    }

    public function test_returns_null_when_not_authenticated()
    {
        Auth::logout();

        $this->currentCompanyService->initialize();

        $this->assertNull($this->currentCompanyService->id());
        $this->assertNull($this->currentCompanyService->get());
        $this->assertFalse($this->currentCompanyService->exists());
    }

    public function test_returns_null_when_user_has_no_companies()
    {
        $userWithoutCompany = User::factory()->create();
        Auth::login($userWithoutCompany);

        $this->currentCompanyService->initialize();

        $this->assertNull($this->currentCompanyService->id());
        $this->assertNull($this->currentCompanyService->get());
        $this->assertFalse($this->currentCompanyService->exists());
    }

    public function test_returns_company_data_when_user_has_access()
    {
        Auth::login($this->user);

        $this->currentCompanyService->initialize();

        $this->assertEquals($this->company->id, $this->currentCompanyService->id());
        $this->assertEquals($this->company->name, $this->currentCompanyService->get()->name);
        $this->assertTrue($this->currentCompanyService->exists());
    }

    public function test_returns_company_from_direct_relationship()
    {
        // Test direct company_id on user
        $this->user->update(['company_id' => $this->company->id]);
        Auth::login($this->user);

        $this->currentCompanyService->initialize();

        $this->assertEquals($this->company->id, $this->currentCompanyService->id());
    }

    public function test_returns_company_from_session()
    {
        Auth::login($this->user);
        session(['current_company_id' => $this->company->id]);

        $this->currentCompanyService->initialize();

        $this->assertEquals($this->company->id, $this->currentCompanyService->id());
    }

    public function test_settings_returns_all_settings()
    {
        Auth::login($this->user);

        $this->currentCompanyService->initialize();

        $settings = $this->currentCompanyService->settings();

        $this->assertEquals('blue', $settings['theme']);
        $this->assertEquals('America/Chicago', $settings['timezone']);
        $this->assertContains('analytics', $settings['features']);
    }

    public function test_settings_returns_specific_setting()
    {
        Auth::login($this->user);

        $this->currentCompanyService->initialize();

        $this->assertEquals('blue', $this->currentCompanyService->settings('theme'));
        $this->assertEquals('America/Chicago', $this->currentCompanyService->settings('timezone'));
        $this->assertEquals('default', $this->currentCompanyService->settings('missing', 'default'));
    }

    public function test_switch_to_valid_company()
    {
        $company2 = Company::factory()->create(['name' => 'Company 2']);
        CompanyUser::create([
            'user_id' => $this->user->id,
            'company_id' => $company2->id
        ]);

        Auth::login($this->user);
        $this->currentCompanyService->initialize();

        // Switch to second company
        $success = $this->currentCompanyService->switchTo($company2->id);

        $this->assertTrue($success);
        $this->assertEquals($company2->id, $this->currentCompanyService->id());
        $this->assertEquals($company2->id, session('current_company_id'));
    }

    public function test_switch_to_invalid_company_fails()
    {
        $unauthorizedCompany = Company::factory()->create(['name' => 'Unauthorized']);

        Auth::login($this->user);
        $this->currentCompanyService->initialize();

        $success = $this->currentCompanyService->switchTo($unauthorizedCompany->id);

        $this->assertFalse($success);
        $this->assertEquals($this->company->id, $this->currentCompanyService->id());
    }

    public function test_update_settings_merges_with_existing()
    {
        Auth::login($this->user);
        $this->currentCompanyService->initialize();

        $success = $this->currentCompanyService->updateSettings([
            'theme' => 'red',
            'new_feature' => 'dashboard'
        ]);

        $this->assertTrue($success);

        $settings = $this->currentCompanyService->settings();
        $this->assertEquals('red', $settings['theme']);
        $this->assertEquals('dashboard', $settings['new_feature']);
        $this->assertEquals('America/Chicago', $settings['timezone']); // Preserved
    }

    public function test_get_user_companies()
    {
        $company2 = Company::factory()->create(['name' => 'Company 2']);
        $company3 = Company::factory()->create(['name' => 'Company 3']);

        CompanyUser::create([
            'user_id' => $this->user->id,
            'company_id' => $company2->id
        ]);
        CompanyUser::create([
            'user_id' => $this->user->id,
            'company_id' => $company3->id
        ]);

        Auth::login($this->user);
        $this->currentCompanyService->initialize();

        $companies = $this->currentCompanyService->getUserCompanies();

        $this->assertCount(3, $companies);
        $this->assertTrue($companies->contains('name', 'Test Company'));
        $this->assertTrue($companies->contains('name', 'Company 2'));
        $this->assertTrue($companies->contains('name', 'Company 3'));
    }

    public function test_caches_company_data()
    {
        Auth::login($this->user);

        // First call should cache the data
        $this->currentCompanyService->initialize();
        $firstCompany = $this->currentCompanyService->get();

        $this->assertInstanceOf(Company::class, $firstCompany);

        // Verify cache key exists
        $cachedCompany = Cache::get("company.{$this->company->id}");
        $this->assertNotNull($cachedCompany);
        $this->assertEquals($this->company->id, $cachedCompany->id);
    }

    public function test_clear_cache_resets_data()
    {
        Auth::login($this->user);

        $this->currentCompanyService->initialize();
        $this->assertNotNull($this->currentCompanyService->get());

        $this->currentCompanyService->clearCache();

        // Cache should be cleared
        $cachedCompany = Cache::get("company.{$this->company->id}");
        $this->assertNull($cachedCompany);
    }

    public function test_facade_works_correctly()
    {
        Auth::login($this->user);

        // Test facade methods
        $this->assertEquals($this->company->id, CurrentCompanyFacade::id());
        $this->assertEquals('Test Company', CurrentCompanyFacade::get()->name);
        $this->assertTrue(CurrentCompanyFacade::exists());
        $this->assertEquals('blue', CurrentCompanyFacade::settings('theme'));

        $companies = CurrentCompanyFacade::getUserCompanies();
        $this->assertCount(1, $companies);
    }

    public function test_multi_company_scenario()
    {
        // Create multiple companies
        $company1 = Company::factory()->create(['name' => 'Company Alpha']);
        $company2 = Company::factory()->create(['name' => 'Company Beta']);

        // User has access to multiple companies
        CompanyUser::create(['user_id' => $this->user->id, 'company_id' => $company1->id]);
        CompanyUser::create(['user_id' => $this->user->id, 'company_id' => $company2->id]);

        Auth::login($this->user);

        // Initially gets first company
        $this->currentCompanyService->initialize();
        $firstCompanyId = $this->currentCompanyService->id();
        $this->assertNotNull($firstCompanyId);

        // Switch to company1
        $success = $this->currentCompanyService->switchTo($company1->id);
        $this->assertTrue($success);
        $this->assertEquals($company1->id, $this->currentCompanyService->id());

        // Switch to company2
        $success = $this->currentCompanyService->switchTo($company2->id);
        $this->assertTrue($success);
        $this->assertEquals($company2->id, $this->currentCompanyService->id());

        // Verify session is updated
        $this->assertEquals($company2->id, session('current_company_id'));
    }

    public function test_loads_only_when_needed()
    {
        Auth::login($this->user);

        // Create service AFTER authentication
        $service = new CurrentCompany();

        // Calling id() should initialize and return the company ID
        $companyId = $service->id();
        $this->assertNotNull($companyId);
        $this->assertEquals($this->company->id, $companyId);

        // Should be loaded now
        $this->assertTrue($service->exists());
    }
}
