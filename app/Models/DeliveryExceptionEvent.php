<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['company_id', 'delivery_exception_id', 'actor_user_id', 'event_type', 'notes'])]
class DeliveryExceptionEvent extends Model
{
    use BelongsToCompany;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Delivery exception events are immutable.'));
        static::deleting(fn (): never => throw new LogicException('Delivery exception events are immutable.'));
    }

    public function deliveryException(): BelongsTo
    {
        return $this->belongsTo(DeliveryException::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
