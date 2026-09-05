<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\DeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'company_id', 'customer_id', 'delivery_personnel_id', 'created_by_user_id', 'reference', 'status',
    'pickup_contact_name', 'pickup_contact_phone', 'pickup_address', 'dropoff_contact_name',
    'dropoff_contact_phone', 'dropoff_address', 'scheduled_at', 'delivery_window_starts_at', 'delivery_window_ends_at', 'assigned_at', 'completed_at', 'notes',
])]
class Delivery extends Model
{
    /** @use HasFactory<DeliveryFactory> */
    use BelongsToCompany, HasFactory;

    public const STATUS_UNASSIGNED = 'unassigned';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_PICKED_UP = 'picked_up';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'delivery_window_starts_at' => 'datetime',
            'delivery_window_ends_at' => 'datetime',
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function deliveryPersonnel(): BelongsTo
    {
        return $this->belongsTo(DeliveryPersonnel::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(DeliveryStatusHistory::class)->latest('created_at');
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(DeliveryException::class)->latest('created_at');
    }

    public function trackingLink(): HasOne
    {
        return $this->hasOne(DeliveryTrackingLink::class);
    }

    public function proof(): HasOne
    {
        return $this->hasOne(DeliveryProof::class);
    }

    public function trackingNotifications(): HasMany
    {
        return $this->hasMany(DeliveryTrackingNotification::class)->latest('created_at');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_UNASSIGNED => 'Unassigned',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_PICKED_UP => 'Picked up',
            self::STATUS_IN_TRANSIT => 'In transit',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
