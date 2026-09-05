<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'public_id', 'name', 'slug', 'business_type', 'contact_name', 'contact_email',
    'contact_phone', 'address', 'status', 'onboarding_method',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_REJECTED = 'rejected';

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(CompanySetting::class);
    }

    public function reviewEvents(): HasMany
    {
        return $this->hasMany(CompanyReviewEvent::class)->latest('created_at');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CompanyAdminInvitation::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function customerPortalAccounts(): HasMany
    {
        return $this->hasMany(CustomerPortalAccount::class);
    }

    public function customerPortalInvitations(): HasMany
    {
        return $this->hasMany(CustomerPortalInvitation::class);
    }

    public function deliveryExceptions(): HasMany
    {
        return $this->hasMany(DeliveryException::class);
    }
}
