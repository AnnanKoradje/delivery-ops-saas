<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyAdminInvitation;
use App\Models\CompanyReviewEvent;
use App\Models\CompanySetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanyOnboardingService
{
    public function submitApplication(array $attributes): Company
    {
        return DB::transaction(function () use ($attributes): Company {
            $company = Company::query()->create(['public_id' => (string) Str::uuid(), 'name' => $attributes['company_name'], 'slug' => $this->uniqueSlug($attributes['company_name']), 'business_type' => $attributes['business_type'] ?? null, 'contact_name' => $attributes['contact_name'], 'contact_email' => Str::lower($attributes['contact_email']), 'contact_phone' => $attributes['contact_phone'] ?? null, 'address' => $attributes['address'] ?? null, 'status' => Company::STATUS_PENDING, 'onboarding_method' => 'self_registered']);
            $this->record($company, null, 'application_submitted', null, Company::STATUS_PENDING);

            return $company;
        });
    }

    public function createManualCompany(array $attributes, User $actor): Company
    {
        return DB::transaction(function () use ($attributes, $actor): Company {
            $company = Company::query()->create(['public_id' => (string) Str::uuid(), 'name' => $attributes['company_name'], 'slug' => $this->uniqueSlug($attributes['company_name']), 'business_type' => $attributes['business_type'] ?? null, 'contact_name' => $attributes['contact_name'], 'contact_email' => Str::lower($attributes['contact_email']), 'contact_phone' => $attributes['contact_phone'] ?? null, 'address' => $attributes['address'] ?? null, 'status' => Company::STATUS_PENDING, 'onboarding_method' => 'platform_created']);
            $this->record($company, $actor, 'company_created_manually', null, Company::STATUS_PENDING);

            return $company;
        });
    }

    public function approve(Company $company, User $actor): string
    {
        if ($company->status !== Company::STATUS_PENDING) {
            throw ValidationException::withMessages(['company' => 'Only pending company applications can be approved.']);
        }

        return DB::transaction(function () use ($company, $actor): string {
            $previousStatus = $company->status;
            $company->update(['status' => Company::STATUS_ACTIVE]);
            CompanySetting::query()->firstOrCreate(['company_id' => $company->getKey()]);
            $this->record($company, $actor, 'company_approved', $previousStatus, Company::STATUS_ACTIVE);

            return $this->createAdminInvitation($company, $actor, $company->contact_email);
        });
    }

    public function reject(Company $company, User $actor, ?string $note): void
    {
        $this->transition($company, $actor, Company::STATUS_REJECTED, 'company_rejected', $note, [Company::STATUS_PENDING]);
    }

    public function suspend(Company $company, User $actor, ?string $note): void
    {
        $this->transition($company, $actor, Company::STATUS_SUSPENDED, 'company_suspended', $note, [Company::STATUS_ACTIVE]);
    }

    public function reactivate(Company $company, User $actor, ?string $note): void
    {
        $this->transition($company, $actor, Company::STATUS_ACTIVE, 'company_reactivated', $note, [Company::STATUS_SUSPENDED]);
    }

    public function createAdminInvitation(Company $company, User $actor, string $email): string
    {
        if ($company->status !== Company::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['company' => 'A Company Admin can be invited only for an active company.']);
        }
        $email = Str::lower($email);
        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'This email already belongs to an existing user.']);
        }
        $token = Str::random(64);
        CompanyAdminInvitation::query()->create(['company_id' => $company->getKey(), 'invited_by_user_id' => $actor->getKey(), 'email' => $email, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(7)]);
        $this->record($company, $actor, 'company_admin_invited', Company::STATUS_ACTIVE, Company::STATUS_ACTIVE, null, ['email' => $email]);

        return $token;
    }

    public function acceptInvitation(CompanyAdminInvitation $invitation, array $attributes): User
    {
        if (! $invitation->isUsable()) {
            throw ValidationException::withMessages(['invitation' => 'This invitation is no longer available.']);
        }

        return DB::transaction(function () use ($invitation, $attributes): User {
            if (User::query()->where('email', $invitation->email)->exists()) {
                throw ValidationException::withMessages(['email' => 'This email already belongs to an existing user.']);
            }
            $user = User::query()->create(['company_id' => $invitation->company_id, 'name' => $attributes['name'], 'email' => $invitation->email, 'password' => $attributes['password'], 'email_verified_at' => now(), 'is_active' => true]);
            $user->assignRole(Role::query()->where('slug', 'company-admin')->firstOrFail(), $invitation->company);
            $invitation->update(['accepted_at' => now()]);
            $this->record($invitation->company, $user, 'company_admin_accepted_invitation', Company::STATUS_ACTIVE, Company::STATUS_ACTIVE);

            return $user;
        });
    }

    private function transition(Company $company, User $actor, string $nextStatus, string $eventType, ?string $note, array $allowedFrom): void
    {
        if (! in_array($company->status, $allowedFrom, true)) {
            throw ValidationException::withMessages(['company' => 'This company cannot transition from its current status.']);
        }
        DB::transaction(function () use ($company, $actor, $nextStatus, $eventType, $note): void {
            $previousStatus = $company->status;
            $company->update(['status' => $nextStatus]);
            $this->record($company, $actor, $eventType, $previousStatus, $nextStatus, $note);
        });
    }

    private function record(Company $company, ?User $actor, string $eventType, ?string $previousStatus, ?string $nextStatus, ?string $note = null, ?array $metadata = null): void
    {
        CompanyReviewEvent::query()->create(['company_id' => $company->getKey(), 'actor_user_id' => $actor?->getKey(), 'event_type' => $eventType, 'previous_status' => $previousStatus, 'new_status' => $nextStatus, 'note' => $note, 'metadata' => $metadata]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'company';
        do {
            $slug = Str::limit($base, 72, '').'-'.Str::lower(Str::random(8));
        } while (Company::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }
}
