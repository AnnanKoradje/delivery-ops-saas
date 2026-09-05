<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use LogicException;

#[Fillable(['company_id', 'name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_assignments')
            ->withPivot('company_id')
            ->withTimestamps();
    }

    public function deliveryPersonnel(): HasOne
    {
        return $this->hasOne(DeliveryPersonnel::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()
            ->where('roles.scope', Role::PLATFORM_SCOPE)
            ->where('roles.slug', 'super-admin')
            ->exists();
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles()
            ->where('roles.slug', $slug)
            ->where(function ($query): void {
                $query->where('roles.scope', Role::PLATFORM_SCOPE)
                    ->orWhere('role_assignments.company_id', $this->company_id);
            })
            ->exists();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->roles()
            ->where(function ($query): void {
                $query->where('roles.scope', Role::PLATFORM_SCOPE)
                    ->orWhere('role_assignments.company_id', $this->company_id);
            })
            ->whereHas('permissions', fn ($query) => $query->where('permissions.slug', $permissionSlug))
            ->exists();
    }

    public function assignRole(Role $role, ?Company $company = null): void
    {
        $companyId = null;

        if ($role->scope === Role::COMPANY_SCOPE) {
            $companyId = $company?->getKey() ?? $this->company_id;

            if ($companyId === null || (int) $this->company_id !== (int) $companyId) {
                throw new LogicException('A company role must match the user\'s company membership.');
            }
        }

        $existing = DB::table('role_assignments')
            ->where('user_id', $this->getKey())
            ->where('role_id', $role->getKey())
            ->when($companyId === null, fn ($query) => $query->whereNull('company_id'), fn ($query) => $query->where('company_id', $companyId))
            ->exists();

        if (! $existing) {
            DB::table('role_assignments')->insert([
                'user_id' => $this->getKey(),
                'role_id' => $role->getKey(),
                'company_id' => $companyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
