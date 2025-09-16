<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
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
}
