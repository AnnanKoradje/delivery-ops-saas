<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function forCompany(?Company $company = null): static
    {
        return $this->state(fn (): array => ['company_id' => $company ?? Company::factory()]);
    }

    public function platformUser(): static
    {
        return $this->state(fn (): array => ['company_id' => null]);
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function withCompanyRole(string $roleSlug, ?Company $company = null): static
    {
        return $this->afterCreating(function (User $user) use ($roleSlug, $company): void {
            $tenant = $company ?? $user->company;
            $role = Role::query()->where('slug', $roleSlug)->where('scope', Role::COMPANY_SCOPE)->firstOrFail();
            $user->assignRole($role, $tenant);
        });
    }
}
