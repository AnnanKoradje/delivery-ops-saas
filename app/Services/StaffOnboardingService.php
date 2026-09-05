<?php

namespace App\Services;

use App\Models\Company;
use App\Models\DeliveryPersonnel;
use App\Models\Role;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StaffOnboardingService
{
    public function invite(User $actor, array $attributes): string
    {
        $company = $actor->company;
        abort_unless($company?->status === Company::STATUS_ACTIVE, 403);
        $email = Str::lower($attributes['email']);
        $role = Role::query()->where('slug', $attributes['role_slug'])->where('scope', Role::COMPANY_SCOPE)->firstOrFail();

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'This email already belongs to an existing user.']);
        }

        return DB::transaction(function () use ($actor, $company, $email, $role): string {
            $token = Str::random(64);
            StaffInvitation::query()->create([
                'company_id' => $company->id,
                'role_id' => $role->id,
                'invited_by_user_id' => $actor->id,
                'email' => $email,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(7),
            ]);

            return $token;
        });
    }

    public function accept(StaffInvitation $invitation, array $attributes): User
    {
        if (! $invitation->isUsable()) {
            throw ValidationException::withMessages(['invitation' => 'This staff invitation is no longer available.']);
        }

        return DB::transaction(function () use ($invitation, $attributes): User {
            if (User::query()->where('email', $invitation->email)->exists()) {
                throw ValidationException::withMessages(['email' => 'This email already belongs to an existing user.']);
            }

            $user = User::query()->create([
                'company_id' => $invitation->company_id,
                'name' => $attributes['name'],
                'email' => $invitation->email,
                'password' => $attributes['password'],
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
            $user->assignRole($invitation->role, $invitation->company);
            $invitation->update(['accepted_at' => now()]);
            DeliveryPersonnel::query()->forCompany($invitation->company)->where('email', $invitation->email)->whereNull('user_id')->update(['user_id' => $user->id]);

            return $user;
        });
    }
}
