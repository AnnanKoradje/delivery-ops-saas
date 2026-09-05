<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryPersonnel;
use App\Models\DeliveryStatusHistory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_dispatcher_can_create_tenant_scoped_customer_and_delivery(): void
    {
        [$company, $dispatcher] = $this->companyUser('dispatcher');

        $this->actingAs($dispatcher)->post(route('tenant.customers.store'), [
            'name' => 'Acme Stores',
            'email' => 'ops@acme.example',
            'phone' => '+1 555 0100',
        ])->assertSessionHas('status');

        $customer = Customer::query()->firstOrFail();
        $this->actingAs($dispatcher)->post(route('tenant.deliveries.store'), [
            'customer_id' => $customer->id,
            'reference' => 'acme-100',
            'pickup_address' => '12 Dock Road',
            'dropoff_address' => '8 Market Street',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', ['company_id' => $company->id, 'name' => 'Acme Stores']);
        $this->assertDatabaseHas('deliveries', ['company_id' => $company->id, 'reference' => 'ACME-100', 'status' => Delivery::STATUS_UNASSIGNED]);
        $this->assertDatabaseHas('delivery_status_histories', ['company_id' => $company->id, 'to_status' => Delivery::STATUS_UNASSIGNED, 'actor_user_id' => $dispatcher->id]);
    }

    public function test_foreign_customer_and_delivery_identifiers_are_not_available_to_another_tenant(): void
    {
        [$companyA, $dispatcherA] = $this->companyUser('dispatcher');
        [$companyB] = $this->companyUser('dispatcher');
        $foreignCustomer = Customer::factory()->for($companyB)->create();
        $foreignDelivery = $this->deliveryFor($companyB, $foreignCustomer);

        $this->actingAs($dispatcherA)->post(route('tenant.deliveries.store'), [
            'customer_id' => $foreignCustomer->id,
            'pickup_address' => '12 Dock Road',
            'dropoff_address' => '8 Market Street',
        ])->assertNotFound();

        $this->actingAs($dispatcherA)->get(route('tenant.deliveries.show', $foreignDelivery))->assertNotFound();
        $this->assertSame($companyA->id, $dispatcherA->company_id);
    }

    public function test_dispatcher_can_assign_only_active_personnel_from_the_current_tenant(): void
    {
        [$companyA, $dispatcher] = $this->companyUser('dispatcher');
        [$companyB] = $this->companyUser('dispatcher');
        $delivery = $this->deliveryFor($companyA);
        $activePersonnel = DeliveryPersonnel::factory()->for($companyA)->create(['status' => DeliveryPersonnel::STATUS_ACTIVE]);
        $inactivePersonnel = DeliveryPersonnel::factory()->for($companyA)->create(['status' => DeliveryPersonnel::STATUS_INACTIVE]);
        $foreignPersonnel = DeliveryPersonnel::factory()->for($companyB)->create(['status' => DeliveryPersonnel::STATUS_ACTIVE]);

        $this->actingAs($dispatcher)->post(route('tenant.deliveries.assign', $delivery), ['delivery_personnel_id' => $inactivePersonnel->id])->assertSessionHasErrors('delivery_personnel_id');
        $this->actingAs($dispatcher)->post(route('tenant.deliveries.assign', $delivery), ['delivery_personnel_id' => $foreignPersonnel->id])->assertNotFound();
        $this->actingAs($dispatcher)->post(route('tenant.deliveries.assign', $delivery), ['delivery_personnel_id' => $activePersonnel->id])->assertSessionHas('status');

        $this->assertDatabaseHas('deliveries', ['id' => $delivery->id, 'delivery_personnel_id' => $activePersonnel->id, 'status' => Delivery::STATUS_ASSIGNED]);
        $this->assertDatabaseHas('delivery_status_histories', ['delivery_id' => $delivery->id, 'to_status' => Delivery::STATUS_ASSIGNED, 'actor_user_id' => $dispatcher->id]);
    }

    public function test_invalid_lifecycle_transition_is_rejected_without_appending_history(): void
    {
        [$company, $dispatcher] = $this->companyUser('dispatcher');
        $delivery = $this->deliveryFor($company);
        $historyCount = $delivery->statusHistory()->count();

        $this->actingAs($dispatcher)->patch(route('tenant.deliveries.status', $delivery), ['status' => Delivery::STATUS_DELIVERED])->assertSessionHasErrors('status');

        $this->assertSame(Delivery::STATUS_UNASSIGNED, $delivery->fresh()->status);
        $this->assertSame($historyCount, $delivery->statusHistory()->count());
    }

    public function test_delivery_personnel_can_see_and_progress_only_own_assigned_delivery_with_audited_actor(): void
    {
        [$company, $dispatcher] = $this->companyUser('dispatcher');
        [, $personnelUser] = $this->companyUserFor($company, 'delivery-personnel');
        [, $otherPersonnelUser] = $this->companyUserFor($company, 'delivery-personnel');
        $personnel = DeliveryPersonnel::factory()->for($company)->create(['user_id' => $personnelUser->id, 'status' => DeliveryPersonnel::STATUS_ACTIVE]);
        $otherPersonnel = DeliveryPersonnel::factory()->for($company)->create(['user_id' => $otherPersonnelUser->id, 'status' => DeliveryPersonnel::STATUS_ACTIVE]);
        $ownDelivery = $this->deliveryFor($company);
        $foreignDelivery = $this->deliveryFor($company);
        $this->actingAs($dispatcher)->post(route('tenant.deliveries.assign', $ownDelivery), ['delivery_personnel_id' => $personnel->id]);
        $this->actingAs($dispatcher)->post(route('tenant.deliveries.assign', $foreignDelivery), ['delivery_personnel_id' => $otherPersonnel->id]);

        $this->actingAs($personnelUser)->get(route('tenant.assigned-deliveries.index'))->assertOk()->assertSee($ownDelivery->reference)->assertDontSee($foreignDelivery->reference);
        $this->actingAs($personnelUser)->patch(route('tenant.assigned-deliveries.status', $foreignDelivery), ['status' => Delivery::STATUS_PICKED_UP])->assertForbidden();
        $this->actingAs($personnelUser)->patch(route('tenant.assigned-deliveries.status', $ownDelivery), ['status' => Delivery::STATUS_PICKED_UP, 'notes' => 'Parcel collected.'])->assertSessionHas('status');

        $this->assertDatabaseHas('delivery_status_histories', ['delivery_id' => $ownDelivery->id, 'to_status' => Delivery::STATUS_PICKED_UP, 'actor_user_id' => $personnelUser->id, 'notes' => 'Parcel collected.']);
    }

    public function test_duplicate_delivery_reference_is_rejected_within_a_company(): void
    {
        [$company, $dispatcher] = $this->companyUser('dispatcher');
        $customer = Customer::factory()->for($company)->create();
        $this->deliveryFor($company, $customer, 'TRACK-100');

        $this->actingAs($dispatcher)->post(route('tenant.deliveries.store'), [
            'customer_id' => $customer->id,
            'reference' => 'track-100',
            'pickup_address' => '12 Dock Road',
            'dropoff_address' => '8 Market Street',
        ])->assertSessionHasErrors('reference');
    }

    public function test_dispatcher_can_edit_only_current_tenant_customer(): void
    {
        [$companyA, $dispatcher] = $this->companyUser('dispatcher');
        [$companyB] = $this->companyUser('dispatcher');
        $customer = Customer::factory()->for($companyA)->create(['name' => 'Original customer']);
        $foreignCustomer = Customer::factory()->for($companyB)->create();

        $this->actingAs($dispatcher)->patch(route('tenant.customers.update', $customer), [
            'name' => 'Updated customer',
            'email' => 'updated@example.test',
        ])->assertRedirect(route('tenant.customers.index'));
        $this->actingAs($dispatcher)->get(route('tenant.customers.edit', $foreignCustomer))->assertNotFound();

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'company_id' => $companyA->id, 'name' => 'Updated customer']);
    }

    public function test_delivery_and_status_history_factories_can_preserve_the_delivery_tenant(): void
    {
        $company = Company::factory()->active()->create();
        $delivery = Delivery::factory()->forCompany($company)->create();
        $history = DeliveryStatusHistory::factory()->forDelivery($delivery)->create();

        $this->assertSame($company->id, $delivery->company_id);
        $this->assertSame($company->id, $delivery->customer->company_id);
        $this->assertSame($company->id, $history->company_id);
    }

    private function deliveryFor(Company $company, ?Customer $customer = null, ?string $reference = null): Delivery
    {
        $customer ??= Customer::factory()->for($company)->create();

        return Delivery::factory()->for($company)->create([
            'customer_id' => $customer->id,
            'reference' => $reference ?? 'DLV-'.fake()->unique()->numerify('#####'),
        ]);
    }

    /** @return array{Company, User} */
    private function companyUser(string $roleSlug): array
    {
        $company = Company::factory()->active()->create();

        return $this->companyUserFor($company, $roleSlug);
    }

    /** @return array{Company, User} */
    private function companyUserFor(Company $company, string $roleSlug): array
    {
        $user = User::factory()->forCompany($company)->withCompanyRole($roleSlug, $company)->create();

        return [$company, $user];
    }
}
