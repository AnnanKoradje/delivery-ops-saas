<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['company_id', 'name', 'email', 'tracking_email_consent_at', 'tracking_email_opted_out_at', 'phone', 'address', 'notes'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'tracking_email_consent_at' => 'datetime',
            'tracking_email_opted_out_at' => 'datetime',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function portalAccount(): HasOne
    {
        return $this->hasOne(CustomerPortalAccount::class);
    }

    public function portalInvitations(): HasMany
    {
        return $this->hasMany(CustomerPortalInvitation::class);
    }
}
