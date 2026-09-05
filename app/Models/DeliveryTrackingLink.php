<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'delivery_id', 'created_by_user_id', 'token_hash', 'revoked_at', 'revoked_by_user_id', 'last_accessed_at'])]
class DeliveryTrackingLink extends Model
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeliveryTrackingLinkEvent::class)->latest('created_at');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
