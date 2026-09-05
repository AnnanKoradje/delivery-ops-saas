<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryPersonnel;
use App\Models\DeliveryProof;
use App\Models\DeliveryProofFile;
use App\Models\DeliveryTrackingNotification;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class ProofOfDeliveryAndTrackingEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_assigned_rider_can_submit_private_photo_and_signature_proof_which_completes_delivery(): void
    {
        Storage::fake('local');
        [$company, $rider, $delivery] = $this->assignedInTransitDelivery();

        $this->actingAs($rider)->post(route('tenant.assigned-deliveries.proof.store', $delivery), [
            'recipient_name' => 'Amina Recipient',
            'notes' => 'Left with recipient.',
            'photos' => [$this->image('front-door.png')],
            'signature' => $this->image('recipient-signature.png'),
        ])->assertSessionHas('status');

        $proof = DeliveryProof::query()->with('files')->firstOrFail();
        $this->assertSame($company->id, $proof->company_id);
        $this->assertSame($rider->id, $proof->submitted_by_user_id);
        $this->assertCount(2, $proof->files);
        $this->assertDatabaseHas('deliveries', ['id' => $delivery->id, 'status' => Delivery::STATUS_DELIVERED]);
        $this->assertDatabaseHas('delivery_status_histories', ['delivery_id' => $delivery->id, 'to_status' => Delivery::STATUS_DELIVERED, 'notes' => 'Proof of delivery submitted.']);
        Storage::disk('local')->assertExists($proof->files->first()->path);

        $this->expectException(LogicException::class);
        $proof->update(['notes' => 'Changed after completion']);
    }

    public function test_rider_cannot_complete_or_submit_proof_for_another_persons_delivery(): void
    {
        Storage::fake('local');
        [$company, $rider, $delivery] = $this->assignedInTransitDelivery();
        $otherRider = User::factory()->forCompany($company)->withCompanyRole('delivery-personnel', $company)->create();
        DeliveryPersonnel::factory()->for($company)->create(['user_id' => $otherRider->id]);

        $this->actingAs($otherRider)->post(route('tenant.assigned-deliveries.proof.store', $delivery), [
            'photos' => [$this->image('unrelated.png')],
        ])->assertForbidden();
        $this->actingAs($rider)->patch(route('tenant.assigned-deliveries.status', $delivery), ['status' => Delivery::STATUS_DELIVERED])->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('delivery_proofs', ['delivery_id' => $delivery->id]);
        $this->assertDatabaseHas('deliveries', ['id' => $delivery->id, 'status' => Delivery::STATUS_IN_TRANSIT]);
    }

    public function test_proof_requires_an_allowed_image_and_evidence_type(): void
    {
        Storage::fake('local');
        [, $rider, $delivery] = $this->assignedInTransitDelivery();

        $this->actingAs($rider)->post(route('tenant.assigned-deliveries.proof.store', $delivery), [
            'photos' => [UploadedFile::fake()->create('unsafe.pdf', 100, 'application/pdf')],
        ])->assertSessionHasErrors('photos.0');
        $this->actingAs($rider)->post(route('tenant.assigned-deliveries.proof.store', $delivery), [])->assertSessionHasErrors(['photos', 'signature']);
    }

    public function test_proof_files_are_streamed_only_to_current_tenant_dispatchers_or_assigned_rider(): void
    {
        Storage::fake('local');
        [$company, $rider, $delivery] = $this->assignedInTransitDelivery();
        $this->actingAs($rider)->post(route('tenant.assigned-deliveries.proof.store', $delivery), ['photos' => [$this->image('proof.png')]]);
        $file = DeliveryProofFile::query()->firstOrFail();
        $dispatcher = User::factory()->forCompany($company)->withCompanyRole('dispatcher', $company)->create();
        [$foreignCompany, $foreignDispatcher] = $this->companyUser('dispatcher');

        $this->actingAs($rider)->get(route('tenant.proof-files.show', $file))->assertOk();
        $this->actingAs($dispatcher)->get(route('tenant.proof-files.show', $file))->assertOk();
        $this->actingAs($foreignDispatcher)->get(route('tenant.proof-files.show', $file))->assertNotFound();
        $this->assertNotSame($company->id, $foreignCompany->id);
    }

    public function test_dispatcher_can_record_a_consent_gated_local_email_preview_without_sending_email(): void
    {
        [$company, $dispatcher] = $this->companyUser('dispatcher');
        $customer = Customer::factory()->for($company)->create(['email' => 'recipient@example.test', 'tracking_email_consent_at' => now()]);
        $delivery = Delivery::factory()->for($company)->create(['customer_id' => $customer->id]);

        $this->actingAs($dispatcher)->post(route('tenant.deliveries.tracking-email.store', $delivery))->assertSessionHas('status');

        $notification = DeliveryTrackingNotification::query()->firstOrFail();
        $this->assertSame(DeliveryTrackingNotification::STATUS_PREVIEWED, $notification->status);
        $this->assertSame($customer->email, $notification->recipient_email);
        $this->assertSame($company->contact_email, $notification->reply_to_email);
        $this->assertDatabaseHas('delivery_tracking_links', ['delivery_id' => $delivery->id]);
    }

    public function test_tracking_email_rejects_missing_consent_and_foreign_delivery_identifiers(): void
    {
        [$companyA, $dispatcherA] = $this->companyUser('dispatcher');
        $noConsentCustomer = Customer::factory()->for($companyA)->create(['email' => 'recipient@example.test']);
        $delivery = Delivery::factory()->for($companyA)->create(['customer_id' => $noConsentCustomer->id]);
        [$companyB] = $this->companyUser('dispatcher');
        $foreignDelivery = Delivery::factory()->for($companyB)->create(['customer_id' => Customer::factory()->for($companyB)]);

        $this->actingAs($dispatcherA)->post(route('tenant.deliveries.tracking-email.store', $delivery))->assertSessionHasErrors('email');
        $this->actingAs($dispatcherA)->post(route('tenant.deliveries.tracking-email.store', $foreignDelivery))->assertNotFound();
        $this->assertDatabaseCount('delivery_tracking_notifications', 0);
    }

    public function test_future_live_mode_records_a_safe_failure_when_no_verified_platform_sender_exists(): void
    {
        config()->set('delivery-email.mode', 'live');
        config()->set('delivery-email.from_address', null);
        [$company, $dispatcher] = $this->companyUser('dispatcher');
        $customer = Customer::factory()->for($company)->create(['email' => 'recipient@example.test', 'tracking_email_consent_at' => now()]);
        $delivery = Delivery::factory()->for($company)->create(['customer_id' => $customer->id]);

        $this->actingAs($dispatcher)->post(route('tenant.deliveries.tracking-email.store', $delivery))->assertSessionHas('status');

        $this->assertDatabaseHas('delivery_tracking_notifications', [
            'delivery_id' => $delivery->id,
            'status' => DeliveryTrackingNotification::STATUS_FAILED,
            'failure_reason' => 'A verified platform sender is not configured.',
        ]);
    }

    /** @return array{Company, User, Delivery} */
    private function assignedInTransitDelivery(): array
    {
        $company = Company::factory()->active()->create();
        $rider = User::factory()->forCompany($company)->withCompanyRole('delivery-personnel', $company)->create();
        $personnel = DeliveryPersonnel::factory()->for($company)->create(['user_id' => $rider->id]);
        $customer = Customer::factory()->for($company)->create();
        $delivery = Delivery::factory()->for($company)->create(['customer_id' => $customer->id, 'delivery_personnel_id' => $personnel->id, 'status' => Delivery::STATUS_IN_TRANSIT]);

        return [$company, $rider, $delivery];
    }

    private function image(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL4nwAAAABJRU5ErkJggg=='));
    }

    /** @return array{Company, User} */
    private function companyUser(string $roleSlug): array
    {
        $company = Company::factory()->active()->create();
        $user = User::factory()->forCompany($company)->withCompanyRole($roleSlug, $company)->create();

        return [$company, $user];
    }
}
