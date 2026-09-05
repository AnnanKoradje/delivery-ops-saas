<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryTrackingLink;
use App\Models\DeliveryTrackingLinkEvent;
use App\Models\User;
use App\Services\DeliveryTrackingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class PublicTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_dispatcher_creates_an_opaque_public_link_and_an_immutable_audit_event(): void
    {
        [$company, $dispatcher] = $this->companyUser('dispatcher');
        $delivery = $this->deliveryFor($company);
        [$link, $token] = app(DeliveryTrackingService::class)->createOrRotate($delivery, $dispatcher);

        $this->assertSame(80, strlen($token));
        $this->assertNotSame($token, $link->token_hash);
        $this->assertSame(hash('sha256', $token), $link->token_hash);
        $this->assertDatabaseHas('delivery_tracking_link_events', ['delivery_tracking_link_id' => $link->id, 'actor_user_id' => $dispatcher->id, 'event_type' => 'created']);

        $this->expectException(LogicException::class);
        DeliveryTrackingLinkEvent::query()->firstOrFail()->delete();
    }

    public function test_rotating_or_revoking_a_link_makes_the_previous_public_url_unavailable(): void
    {
        [$company, $dispatcher] = $this->companyUser('dispatcher');
        $delivery = $this->deliveryFor($company);
        [$link, $firstToken] = app(DeliveryTrackingService::class)->createOrRotate($delivery, $dispatcher);
        [, $secondToken] = app(DeliveryTrackingService::class)->createOrRotate($delivery, $dispatcher);

        $this->get(route('tracking.show', $firstToken))->assertNotFound()->assertSee('This tracking link is unavailable');
        $this->get(route('tracking.show', $secondToken))->assertOk();
        app(DeliveryTrackingService::class)->revoke($link->fresh(), $dispatcher);

        $this->get(route('tracking.show', $secondToken))->assertNotFound()->assertSee('This tracking link is unavailable');
        $this->get(route('tracking.show', str_repeat('x', 80)))->assertNotFound()->assertSee('This tracking link is unavailable');
        $this->assertDatabaseHas('delivery_tracking_link_events', ['delivery_tracking_link_id' => $link->id, 'event_type' => 'rotated']);
        $this->assertDatabaseHas('delivery_tracking_link_events', ['delivery_tracking_link_id' => $link->id, 'event_type' => 'revoked']);
    }

    public function test_public_tracking_exposes_only_safe_status_history_and_optional_window(): void
    {
        [$company, $dispatcher] = $this->companyUser('dispatcher');
        $customer = Customer::factory()->for($company)->create(['name' => 'Private Recipient', 'phone' => '+1 555 0199', 'address' => '19 Recipient Street']);
        $delivery = Delivery::factory()->for($company)->create([
            'customer_id' => $customer->id,
            'reference' => 'SAFE-TRACK-001',
            'pickup_contact_name' => 'Private Sender',
            'pickup_contact_phone' => '+1 555 0100',
            'pickup_address' => '1 Confidential Dock',
            'dropoff_contact_name' => 'Private Recipient',
            'dropoff_contact_phone' => '+1 555 0199',
            'dropoff_address' => '19 Recipient Street',
            'notes' => 'Internal operational note.',
            'delivery_window_starts_at' => '2026-09-01 13:00:00',
            'delivery_window_ends_at' => '2026-09-01 15:00:00',
        ]);
        [, $token] = app(DeliveryTrackingService::class)->createOrRotate($delivery, $dispatcher);

        $this->get(route('tracking.show', $token))->assertOk()->assertSee('SAFE-TRACK-001')->assertSee('Sep 1, 1:00 PM')->assertDontSee('Private Recipient')->assertDontSee('Private Sender')->assertDontSee('Confidential Dock')->assertDontSee('19 Recipient Street')->assertDontSee('Internal operational note.')->assertDontSee('+1 555 0199');
        $this->assertNotNull(DeliveryTrackingLink::query()->firstOrFail()->fresh()->last_accessed_at);
    }

    public function test_dispatcher_can_update_a_valid_delivery_window_but_invalid_window_is_rejected(): void
    {
        [$company, $dispatcher] = $this->companyUser('dispatcher');
        $delivery = $this->deliveryFor($company);

        $this->actingAs($dispatcher)->post(route('tenant.deliveries.tracking-window.update', $delivery), [
            'delivery_window_starts_at' => '2026-09-01 13:00:00',
            'delivery_window_ends_at' => '2026-09-01 15:00:00',
        ])->assertSessionHas('status');
        $this->actingAs($dispatcher)->post(route('tenant.deliveries.tracking-window.update', $delivery), [
            'delivery_window_starts_at' => '2026-09-01 15:00:00',
            'delivery_window_ends_at' => '2026-09-01 13:00:00',
        ])->assertSessionHasErrors('delivery_window_ends_at');

        $this->assertDatabaseHas('deliveries', ['id' => $delivery->id, 'delivery_window_starts_at' => '2026-09-01 13:00:00', 'delivery_window_ends_at' => '2026-09-01 15:00:00']);
    }

    public function test_tracking_link_management_rejects_foreign_deliveries_and_non_dispatcher_staff(): void
    {
        [$companyA, $dispatcherA] = $this->companyUser('dispatcher');
        [$companyB] = $this->companyUser('dispatcher');
        $foreignDelivery = $this->deliveryFor($companyB);
        $rider = User::factory()->forCompany($companyA)->withCompanyRole('delivery-personnel', $companyA)->create();
        $ownDelivery = $this->deliveryFor($companyA);

        $this->actingAs($dispatcherA)->post(route('tenant.deliveries.tracking-link.create', $foreignDelivery))->assertNotFound();
        $this->actingAs($rider)->post(route('tenant.deliveries.tracking-link.create', $ownDelivery))->assertForbidden();
    }

    public function test_dispatcher_receives_a_transient_copyable_link_after_issuing_one(): void
    {
        [$company, $dispatcher] = $this->companyUser('dispatcher');
        $delivery = $this->deliveryFor($company);

        $this->actingAs($dispatcher)->post(route('tenant.deliveries.tracking-link.create', $delivery))->assertSessionHas('tracking_link');
        $this->actingAs($dispatcher)->withSession(['tracking_link' => 'https://example.test/track/'.str_repeat('x', 80)])->get(route('tenant.deliveries.show', $delivery))->assertOk()->assertSee('Copy link')->assertSee('not stored in recoverable form');
    }

    public function test_tracking_link_is_unavailable_when_its_company_is_suspended(): void
    {
        [$company, $dispatcher] = $this->companyUser('dispatcher');
        $delivery = $this->deliveryFor($company);
        [, $token] = app(DeliveryTrackingService::class)->createOrRotate($delivery, $dispatcher);
        $company->update(['status' => Company::STATUS_SUSPENDED]);

        $this->get(route('tracking.show', $token))->assertNotFound();
    }

    public function test_public_tracking_route_rate_limits_repeated_unknown_tokens(): void
    {
        $unknownToken = str_repeat('x', 80);

        foreach (range(1, 20) as $attempt) {
            $this->get(route('tracking.show', $unknownToken))->assertNotFound();
        }

        $this->get(route('tracking.show', $unknownToken))->assertStatus(429);
    }

    private function deliveryFor(Company $company): Delivery
    {
        $customer = Customer::factory()->for($company)->create();

        return Delivery::factory()->for($company)->create(['customer_id' => $customer->id]);
    }

    /** @return array{Company, User} */
    private function companyUser(string $roleSlug): array
    {
        $company = Company::factory()->active()->create();
        $user = User::factory()->forCompany($company)->withCompanyRole($roleSlug, $company)->create();

        return [$company, $user];
    }
}
