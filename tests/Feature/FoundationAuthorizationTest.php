<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy\CompanyContext;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_configured_super_admin_seed_is_platform_only_and_can_access_oversight_dashboard(): void
    {
        config()->set('platform.super_admin.email', 'owner@example.test');
        config()->set('platform.super_admin.password', 'secure-test-password');
        config()->set('platform.super_admin.name', 'Platform Owner');
        $this->seed(DatabaseSeeder::class);
        $superAdmin = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $this->assertNull($superAdmin->company_id);
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->actingAs($superAdmin)->get(route('platform.dashboard'))->assertOk();
    }

    public function test_company_user_cannot_open_platform_oversight_dashboard(): void
    {
        [, $user] = $this->companyUserWithRole('company-admin');
        $this->actingAs($user)->get(route('platform.dashboard'))->assertForbidden();
    }

    public function test_company_scoped_query_hides_another_company_setting(): void
    {
        $companyA = Company::factory()->active()->create();
        $companyB = Company::factory()->active()->create();
        $settingB = CompanySetting::factory()->for($companyB)->create();
        app(CompanyContext::class)->setCompany($companyA);
        $this->assertNull(CompanySetting::query()->find($settingB->getKey()));
        app(CompanyContext::class)->clear();
    }

    public function test_guessed_company_setting_identifier_returns_not_found_for_another_tenant(): void
    {
        [, $companyAdmin] = $this->companyUserWithRole('company-admin');
        $otherSetting = CompanySetting::factory()->for(Company::factory()->active())->create();
        $this->actingAs($companyAdmin)->get(route('tenant.settings.show', $otherSetting->getKey()))->assertNotFound();
    }

    public function test_dispatcher_cannot_view_sensitive_settings_inside_own_company(): void
    {
        [$company, $dispatcher] = $this->companyUserWithRole('dispatcher');
        $setting = CompanySetting::factory()->for($company)->create();
        $this->actingAs($dispatcher)->get(route('tenant.settings.show', $setting->getKey()))->assertForbidden();
    }

    public function test_public_registration_is_not_exposed_before_company_onboarding_phase(): void
    {
        $this->get('/register')->assertNotFound();
    }

    /** @return array{Company, User} */
    private function companyUserWithRole(string $roleSlug): array
    {
        $company = Company::factory()->active()->create();
        $user = User::factory()->forCompany($company)->create();
        $user->assignRole(Role::query()->where('slug', $roleSlug)->firstOrFail(), $company);

        return [$company, $user];
    }
}
