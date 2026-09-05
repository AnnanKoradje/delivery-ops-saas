<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'delivery_id', 'reported_by_user_id', 'resolved_by_user_id', 'type', 'description', 'status', 'resolved_at'])]
class DeliveryException extends Model
{
    use BelongsToCompany;

    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    public static function types(): array
    {
        return [
            'address_issue' => 'Address issue',
            'customer_unavailable' => 'Customer unavailable',
            'damaged_package' => 'Damaged package',
            'vehicle_issue' => 'Vehicle issue',
            'other' => 'Other operational issue',
        ];
    }

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeliveryExceptionEvent::class)->latest('created_at');
    }
}
