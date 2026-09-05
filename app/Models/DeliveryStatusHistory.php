<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\DeliveryStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['company_id', 'delivery_id', 'actor_user_id', 'from_status', 'to_status', 'notes'])]
class DeliveryStatusHistory extends Model
{
    /** @use HasFactory<DeliveryStatusHistoryFactory> */
    use BelongsToCompany, HasFactory;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Delivery status history is immutable.'));
        static::deleting(fn (): never => throw new LogicException('Delivery status history is immutable.'));
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
