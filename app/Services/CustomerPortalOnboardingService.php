<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPortalAccount;
use App\Models\CustomerPortalInvitation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerPortalOnboardingService
{
    /** @return array{CustomerPortalInvitation, string} */
    public function invite(User $actor, Customer $customer): array
    {
        if ($customer->company_id !== $actor->company_id) {
            throw new AuthorizationException('The customer must belong to the current company.');
        }

        if (blank($customer->email)) {
            throw ValidationException::withMessages(['customer' => 'Add a customer email before issuing portal access.']);
        }

        if ($customer->portalAccount()->exists()) {
            throw ValidationException::withMessages(['customer' => 'This customer already has portal access.']);
        }

        $token = Str::random(64);
        $invitation = DB::transaction(function () use ($actor, $customer, $token): CustomerPortalInvitation {
            return CustomerPortalInvitation::query()->create([
                'company_id' => $customer->company_id,
                'customer_id' => $customer->getKey(),
                'invited_by_user_id' => $actor->getKey(),
                'email' => Str::lower($customer->email),
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(7),
            ]);
        });

        return [$invitation, $token];
    }

    public function accept(string $token, string $password): CustomerPortalAccount
    {
        $invitation = CustomerPortalInvitation::query()
            ->withoutGlobalScopes()
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        if (! $invitation->isUsable()) {
            throw ValidationException::withMessages(['token' => 'This customer portal invitation is no longer valid.']);
        }

        return DB::transaction(function () use ($invitation, $password): CustomerPortalAccount {
            if (CustomerPortalAccount::query()->withoutGlobalScopes()->where('customer_id', $invitation->customer_id)->exists()) {
                throw ValidationException::withMessages(['token' => 'This customer portal invitation has already been used.']);
            }

            $account = CustomerPortalAccount::query()->create([
                'company_id' => $invitation->company_id,
                'customer_id' => $invitation->customer_id,
                'email' => $invitation->email,
                'password' => Hash::make($password),
            ]);

            $invitation->update(['accepted_at' => now()]);

            return $account;
        });
    }

    public function invitationForToken(string $token): CustomerPortalInvitation
    {
        $invitation = CustomerPortalInvitation::query()
            ->withoutGlobalScopes()
            ->where('token_hash', hash('sha256', $token))
            ->with('customer')
            ->firstOrFail();

        abort_unless($invitation->isUsable(), 404);

        return $invitation;
    }
}
