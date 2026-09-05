<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\DeliveryPersonnel;
use App\Models\Role;
use App\Models\StaffInvitation;
use App\Models\User;
use App\Services\StaffOnboardingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_company_admin_can_update_only_own_company_profile_and_terminology(): void
    {
        [$company, $admin] = $this->companyUser('company-admin');
        $setting = CompanySetting::factory()->for($company)->create();

        $this->actingAs($admin)->patch(route('tenant.profile.update'), ['name' => 'Harbor Operations', 'business_type' => 'Courier', 'contact_name' => 'Morgan Lee', 'contact_email' => 'morgan@harbor.example', 'contact_phone' => '+1 555 0101', 'address' => 'Harbor Road'])->assertSessionHas('status');
        $this->actingAs($admin)->patch(route('tenant.settings.update', $setting), ['personnel_label_singular' => 'Rider', 'personnel_label_plural' => 'Riders', 'operation_label_singular' => 'Shipment', 'operation_label_plural' => 'Shipments', 'time_zone' => 'America/New_York', 'tracking_enabled' => '1'])->assertSessionHas('status');

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Harbor Operations']);
        $this->assertDatabaseHas('company_settings', ['id' => $setting->id, 'personnel_label_plural' => 'Riders', 'operation_label_plural' => 'Shipments']);
    }

    public function test_company_admin_can_invite_company_scoped_staff_and_acceptance_assigns_only_requested_role(): void
    {
        [$company, $admin] = $this->companyUser('company-admin');
        $token = app(StaffOnboardingService::class)->invite($admin, ['email' => 'dispatch@harbor.example', 'role_slug' => 'dispatcher']);

        $response = $this->post(route('tenant.staff-invitation.store', $token), ['name' => 'Dispatch User', 'password' => 'a-secure-test-password', 'password_confirmation' => 'a-secure-test-password']);

        $response->assertRedirect(route('dashboard'));
        $user = User::query()->where('email', 'dispatch@harbor.example')->firstOrFail();
        $this->assertSame($company->id, $user->company_id);
        $this->assertTrue($user->hasRole('dispatcher'));
        $this->assertFalse($user->hasRole('company-admin'));
        $this->assertNotNull(StaffInvitation::query()->where('email', 'dispatch@harbor.example')->firstOrFail()->accepted_at);
    }

    public function test_dispatcher_cannot_manage_staff_or_delivery_personnel(): void
    {
        [, $dispatcher] = $this->companyUser('dispatcher');

        $this->actingAs($dispatcher)->get(route('tenant.staff.index'))->assertForbidden();
        $this->actingAs($dispatcher)->get(route('tenant.personnel.index'))->assertForbidden();
    }

    public function test_delivery_personnel_records_are_tenant_scoped_and_guessed_foreign_identifier_returns_not_found(): void
    {
        [$companyA, $adminA] = $this->companyUser('company-admin');
        [$companyB] = $this->companyUser('company-admin');
        $foreignPersonnel = DeliveryPersonnel::factory()->for($companyB)->create();

        $this->actingAs($adminA)->post(route('tenant.personnel.store'), ['full_name' => 'Ava Rider', 'email' => 'ava@company-a.example', 'employee_code' => 'A-100'])->assertSessionHas('status');
        $this->actingAs($adminA)->patch(route('tenant.personnel.status', $foreignPersonnel), ['status' => 'inactive'])->assertNotFound();

        $this->assertDatabaseHas('delivery_personnel', ['company_id' => $companyA->id, 'full_name' => 'Ava Rider']);
        $this->assertSame(DeliveryPersonnel::STATUS_ACTIVE, $foreignPersonnel->fresh()->status);
    }

    public function test_deactivated_staff_account_cannot_access_the_workspace_dashboard(): void
    {
        [$company, $admin] = $this->companyUser('company-admin');
        $staff = User::factory()->forCompany($company)->create(['is_active' => false]);
        $staff->assignRole(Role::query()->where('slug', 'dispatcher')->firstOrFail(), $company);

        $this->actingAs($staff)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('tenant.staff.index'))->assertSee($staff->email);
    }

    /** @return array{Company, User} */
    private function companyUser(string $roleSlug): array
    {
        $company = Company::factory()->active()->create();
        $user = User::factory()->forCompany($company)->withCompanyRole($roleSlug, $company)->create();

        return [$company, $user];
    }
}
