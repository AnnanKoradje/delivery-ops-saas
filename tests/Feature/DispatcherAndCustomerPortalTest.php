<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPortalAccount;
use App\Models\Delivery;
use App\Models\DeliveryException;
use App\Models\DeliveryExceptionEvent;
use App\Models\DeliveryPersonnel;
use App\Models\User;
use App\Services\CustomerPortalOnboardingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class DispatcherAndCustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_dispatcher_bulk_assignment_accepts_only_current_company_deliveries_and_active_personnel(): void
    {
        [$companyA, $dispatcher] = $this->companyUser('dispatcher');
        [$companyB] = $this->companyUser('dispatcher');
        $personnel = DeliveryPersonnel::factory()->for($companyA)->create(['status' => DeliveryPersonnel::STATUS_ACTIVE]);
        $deliveryOne = $this->deliveryFor($companyA);
        $deliveryTwo = $this->deliveryFor($companyA);
        $foreignDelivery = $this->deliveryFor($companyB);

        $this->actingAs($dispatcher)->post(route('tenant.deliveries.bulk-assign'), [
            'delivery_ids' => [$deliveryOne->id, $foreignDelivery->id],
            'delivery_personnel_id' => $personnel->id,
        ])->assertForbidden();

        $this->actingAs($dispatcher)->post(route('tenant.deliveries.bulk-assign'), [
            'delivery_ids' => [$deliveryOne->id, $deliveryTwo->id],
            'delivery_personnel_id' => $personnel->id,
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('deliveries', ['id' => $deliveryOne->id, 'delivery_personnel_id' => $personnel->id, 'status' => Delivery::STATUS_ASSIGNED]);
        $this->assertDatabaseHas('deliveries', ['id' => $deliveryTwo->id, 'delivery_personnel_id' => $personnel->id, 'status' => Delivery::STATUS_ASSIGNED]);
        $this->assertSame(Delivery::STATUS_UNASSIGNED, $foreignDelivery->fresh()->status);
    }

    public function test_dispatcher_cancellation_and_exception_resolution_create_auditable_records(): void
    {
        [$company, $dispatcher] = $this->companyUser('dispatcher');
        $delivery = $this->deliveryFor($company);

        $this->actingAs($dispatcher)->post(route('tenant.deliveries.exceptions.store', $delivery), [
            'type' => 'address_issue',
            'description' => 'Customer requested address clarification.',
        ])->assertSessionHas('status');
        $exception = DeliveryException::query()->firstOrFail();
        $this->actingAs($dispatcher)->post(route('tenant.exceptions.resolve', $exception), ['notes' => 'Address confirmed.'])->assertSessionHas('status');
        $this->actingAs($dispatcher)->post(route('tenant.deliveries.cancel', $delivery), ['notes' => 'Customer cancelled before dispatch.'])->assertSessionHas('status');

        $this->assertDatabaseHas('delivery_exceptions', ['id' => $exception->id, 'status' => DeliveryException::STATUS_RESOLVED, 'resolved_by_user_id' => $dispatcher->id]);
        $this->assertDatabaseHas('delivery_exception_events', ['delivery_exception_id' => $exception->id, 'event_type' => 'reported', 'actor_user_id' => $dispatcher->id]);
        $this->assertDatabaseHas('delivery_exception_events', ['delivery_exception_id' => $exception->id, 'event_type' => 'resolved', 'actor_user_id' => $dispatcher->id]);
        $this->assertDatabaseHas('delivery_status_histories', ['delivery_id' => $delivery->id, 'to_status' => Delivery::STATUS_CANCELLED, 'notes' => 'Customer cancelled before dispatch.']);

        $this->expectException(LogicException::class);
        DeliveryExceptionEvent::query()->firstOrFail()->delete();
    }

    public function test_operations_report_does_not_include_another_company_delivery_counts(): void
    {
        [$companyA, $dispatcher] = $this->companyUser('dispatcher');
        [$companyB] = $this->companyUser('dispatcher');
        $this->deliveryFor($companyA);
        $this->deliveryFor($companyA);
        $this->deliveryFor($companyB);
        $this->deliveryFor($companyB);
        $this->deliveryFor($companyB);

        $this->actingAs($dispatcher)->get(route('tenant.operations.reports'))->assertOk()->assertSee('Unassigned')->assertSee('2');
    }

    public function test_dispatcher_can_issue_one_time_customer_portal_invitation_and_customer_can_activate_it(): void
    {
        [$company, $dispatcher] = $this->companyUser('dispatcher');
        $customer = Customer::factory()->for($company)->create(['email' => 'portal-customer@example.test']);
        [$invitation, $token] = app(CustomerPortalOnboardingService::class)->invite($dispatcher, $customer);

        $this->post(route('portal.invitation.store', $token), [
            'password' => 'secure-customer-password',
            'password_confirmation' => 'secure-customer-password',
        ])->assertRedirect(route('portal.deliveries.index'));

        $this->assertAuthenticated('customer');
        $this->assertDatabaseHas('customer_portal_accounts', ['customer_id' => $customer->id, 'company_id' => $company->id, 'email' => $customer->email]);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_customer_portal_lists_and_tracks_only_its_own_company_customer_deliveries(): void
    {
        [$companyA] = $this->companyUser('dispatcher');
        [$companyB] = $this->companyUser('dispatcher');
        $customerA = Customer::factory()->for($companyA)->create(['email' => 'a@example.test']);
        $otherCustomer = Customer::factory()->for($companyA)->create(['email' => 'b@example.test']);
        $ownDelivery = $this->deliveryFor($companyA, $customerA, 'PORTAL-OWN');
        $otherCustomerDelivery = $this->deliveryFor($companyA, $otherCustomer, 'PORTAL-OTHER');
        $foreignDelivery = $this->deliveryFor($companyB, null, 'PORTAL-FOREIGN');
        $account = CustomerPortalAccount::query()->create(['company_id' => $companyA->id, 'customer_id' => $customerA->id, 'email' => $customerA->email, 'password' => Hash::make('secure-customer-password')]);

        $this->actingAs($account, 'customer')->get(route('portal.deliveries.index'))->assertOk()->assertSee($ownDelivery->reference)->assertDontSee($otherCustomerDelivery->reference)->assertDontSee($foreignDelivery->reference);
        $this->actingAs($account, 'customer')->get(route('portal.deliveries.show', $otherCustomerDelivery))->assertNotFound();
        $this->actingAs($account, 'customer')->get(route('portal.deliveries.show', $foreignDelivery))->assertNotFound();
    }

    public function test_customer_can_update_only_the_profile_connected_to_the_portal_account(): void
    {
        [$company] = $this->companyUser('dispatcher');
        $customer = Customer::factory()->for($company)->create(['email' => 'profile@example.test']);
        $account = CustomerPortalAccount::query()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'email' => $customer->email, 'password' => Hash::make('secure-customer-password')]);

        $this->actingAs($account, 'customer')->patch(route('portal.profile.update'), ['name' => 'Updated Customer', 'phone' => '+1 555 0100', 'address' => '45 Updated Road'])->assertSessionHas('status');

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Updated Customer', 'phone' => '+1 555 0100']);
    }

    public function test_delivery_personnel_cannot_access_dispatcher_bulk_controls_or_reporting(): void
    {
        [$company] = $this->companyUser('dispatcher');
        $rider = User::factory()->forCompany($company)->withCompanyRole('delivery-personnel', $company)->create();
        $delivery = $this->deliveryFor($company);

        $this->actingAs($rider)->get(route('tenant.operations.reports'))->assertForbidden();
        $this->actingAs($rider)->post(route('tenant.deliveries.bulk-assign'), ['delivery_ids' => [$delivery->id], 'delivery_personnel_id' => 1])->assertForbidden();
        $this->actingAs($rider)->post(route('tenant.deliveries.exceptions.store', $delivery), ['type' => 'other', 'description' => 'Attempt'])->assertForbidden();
    }

    public function test_customer_portal_session_is_not_a_staff_workspace_session(): void
    {
        [$company] = $this->companyUser('dispatcher');
        $customer = Customer::factory()->for($company)->create(['email' => 'portal-only@example.test']);
        $account = CustomerPortalAccount::query()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'email' => $customer->email, 'password' => Hash::make('secure-customer-password')]);

        $this->actingAs($account, 'customer')->get(route('tenant.deliveries.index'))->assertForbidden();
    }

    private function deliveryFor(Company $company, ?Customer $customer = null, ?string $reference = null): Delivery
    {
        $customer ??= Customer::factory()->for($company)->create();

        return Delivery::factory()->for($company)->create(['customer_id' => $customer->id, 'reference' => $reference ?? 'P5-'.fake()->unique()->numerify('#####')]);
    }

    /** @return array{Company, User} */
    private function companyUser(string $roleSlug): array
    {
        $company = Company::factory()->active()->create();
        $user = User::factory()->forCompany($company)->withCompanyRole($roleSlug, $company)->create();

        return [$company, $user];
    }
}
