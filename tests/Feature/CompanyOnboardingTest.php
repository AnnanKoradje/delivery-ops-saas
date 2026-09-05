<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyAdminInvitation;
use App\Models\CompanyReviewEvent;
use App\Models\CompanySetting;
use App\Models\Role;
use App\Models\User;
use App\Services\CompanyOnboardingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_public_company_application_creates_a_pending_company_and_audit_event(): void
    {
        $response = $this->post(route('onboarding.application.store'), [
            'company_name' => 'Northstar Logistics',
            'business_type' => 'Courier service',
            'contact_name' => 'Alex Morgan',
            'contact_email' => 'alex@northstar.example',
            'contact_phone' => '+1 555 0100',
        ]);

        $response->assertRedirect(route('onboarding.application.submitted'));
        $company = Company::query()->where('contact_email', 'alex@northstar.example')->firstOrFail();
        $this->assertSame(Company::STATUS_PENDING, $company->status);
        $this->assertDatabaseHas('company_review_events', ['company_id' => $company->id, 'event_type' => 'application_submitted']);
        $this->assertDatabaseMissing('users', ['email' => 'alex@northstar.example']);
    }

    public function test_super_admin_can_create_an_auditable_manual_pending_company_record(): void
    {
        $actor = $this->platformAdmin();

        $response = $this->actingAs($actor)->post(route('platform.companies.store'), [
            'company_name' => 'Assisted Onboarding Co',
            'contact_name' => 'Morgan Lee',
            'contact_email' => 'morgan@assisted.example',
        ]);

        $company = Company::query()->where('contact_email', 'morgan@assisted.example')->firstOrFail();
        $response->assertRedirect(route('platform.companies.show', $company));
        $this->assertSame(Company::STATUS_PENDING, $company->status);
        $this->assertSame('platform_created', $company->onboarding_method);
        $this->assertDatabaseHas('company_review_events', ['company_id' => $company->id, 'actor_user_id' => $actor->id, 'event_type' => 'company_created_manually']);
    }

    public function test_super_admin_approval_activates_company_creates_defaults_and_provisions_one_time_invitation(): void
    {
        $company = Company::factory()->create(['status' => Company::STATUS_PENDING, 'contact_email' => 'admin@northstar.example']);
        $actor = $this->platformAdmin();

        $token = app(CompanyOnboardingService::class)->approve($company, $actor);

        $company->refresh();
        $this->assertSame(Company::STATUS_ACTIVE, $company->status);
        $this->assertDatabaseHas('company_settings', ['company_id' => $company->id]);
        $this->assertDatabaseHas('company_review_events', ['company_id' => $company->id, 'event_type' => 'company_approved']);
        $this->assertNotEmpty($token);
        $invitation = CompanyAdminInvitation::query()->where('company_id', $company->id)->firstOrFail();
        $this->assertTrue($invitation->isUsable());
    }

    public function test_company_admin_acceptance_creates_only_a_company_scoped_admin_account(): void
    {
        $company = Company::factory()->active()->create(['contact_email' => 'admin@tenant.example']);
        CompanySetting::factory()->for($company)->create();
        $token = app(CompanyOnboardingService::class)->createAdminInvitation($company, $this->platformAdmin(), 'admin@tenant.example');

        $response = $this->post(route('onboarding.invitation.store', $token), [
            'name' => 'Tenant Administrator',
            'password' => 'a-secure-test-password',
            'password_confirmation' => 'a-secure-test-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $user = User::query()->where('email', 'admin@tenant.example')->firstOrFail();
        $this->assertSame($company->id, $user->company_id);
        $this->assertTrue($user->hasRole('company-admin'));
        $this->assertFalse($user->isSuperAdmin());
        $this->assertNotNull(CompanyAdminInvitation::query()->where('company_id', $company->id)->firstOrFail()->accepted_at);
    }

    public function test_company_user_cannot_manage_another_company_or_platform_lifecycle_controls(): void
    {
        $company = Company::factory()->create(['status' => Company::STATUS_PENDING]);
        $tenantCompany = Company::factory()->active()->create();
        $tenant = User::factory()->forCompany($tenantCompany)->create();
        $tenant->assignRole(Role::query()->where('slug', 'company-admin')->firstOrFail(), $tenant->company);

        $this->actingAs($tenant)->post(route('platform.companies.approve', $company))->assertForbidden();
        $this->actingAs($tenant)->post(route('platform.companies.store'), [
            'company_name' => 'Unauthorized Company',
            'contact_name' => 'Unauthorized User',
            'contact_email' => 'unauthorized@example.test',
        ])->assertForbidden();
        $this->assertSame(Company::STATUS_PENDING, $company->fresh()->status);
    }

    public function test_platform_lifecycle_requires_valid_transition_order(): void
    {
        $company = Company::factory()->active()->create();
        $actor = $this->platformAdmin();
        app(CompanyOnboardingService::class)->suspend($company, $actor, 'Verification required');
        $this->assertSame(Company::STATUS_SUSPENDED, $company->fresh()->status);
        app(CompanyOnboardingService::class)->reactivate($company, $actor, 'Verification complete');
        $this->assertSame(Company::STATUS_ACTIVE, $company->fresh()->status);
        $this->assertSame(2, CompanyReviewEvent::query()->where('company_id', $company->id)->count());
    }

    public function test_super_admin_can_reject_a_pending_application_with_an_auditable_reason(): void
    {
        $company = Company::factory()->create(['status' => Company::STATUS_PENDING]);

        app(CompanyOnboardingService::class)->reject($company, $this->platformAdmin(), 'Required compliance information was not supplied.');

        $this->assertSame(Company::STATUS_REJECTED, $company->fresh()->status);
        $this->assertDatabaseHas('company_review_events', [
            'company_id' => $company->id,
            'event_type' => 'company_rejected',
            'note' => 'Required compliance information was not supplied.',
        ]);
    }

    public function test_expired_company_admin_invitation_is_not_exposed_or_accepted(): void
    {
        $company = Company::factory()->active()->create(['contact_email' => 'expired@tenant.example']);
        $token = app(CompanyOnboardingService::class)->createAdminInvitation($company, $this->platformAdmin(), 'expired@tenant.example');
        CompanyAdminInvitation::query()->where('company_id', $company->id)->update(['expires_at' => now()->subMinute()]);

        $this->get(route('onboarding.invitation.show', $token))->assertGone();
        $this->post(route('onboarding.invitation.store', $token), [
            'name' => 'Expired Invitation User',
            'password' => 'a-secure-test-password',
            'password_confirmation' => 'a-secure-test-password',
        ])->assertGone();
        $this->assertDatabaseMissing('users', ['email' => 'expired@tenant.example']);
    }

    public function test_tenant_dashboard_reflects_company_lifecycle_status_and_suspended_tenants_are_blocked_from_workspace_routes(): void
    {
        $activeCompany = Company::factory()->active()->create();
        $activeUser = User::factory()->forCompany($activeCompany)->create();
        $activeUser->assignRole(Role::query()->where('slug', 'company-admin')->firstOrFail(), $activeCompany);
        $this->actingAs($activeUser)->get(route('dashboard'))->assertSee('Your workspace is active.');

        $suspendedCompany = Company::factory()->active()->create();
        $suspendedUser = User::factory()->forCompany($suspendedCompany)->create();
        $suspendedUser->assignRole(Role::query()->where('slug', 'company-admin')->firstOrFail(), $suspendedCompany);
        $setting = CompanySetting::factory()->for($suspendedCompany)->create();
        $suspendedCompany->update(['status' => Company::STATUS_SUSPENDED]);

        $this->actingAs($suspendedUser)->get(route('dashboard'))->assertSee('Workspace access is currently unavailable.');
        $this->actingAs($suspendedUser)->get(route('tenant.settings.show', $setting))->assertForbidden();
    }

    private function platformAdmin(): User
    {
        $user = User::factory()->platformUser()->create();
        $user->assignRole(Role::query()->where('slug', 'super-admin')->firstOrFail());

        return $user;
    }
}
