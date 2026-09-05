<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

#[Fillable(['company_id', 'delivery_tracking_link_id', 'actor_user_id', 'event_type'])]
class DeliveryTrackingLinkEvent extends Model
{
    use BelongsToCompany;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Tracking-link audit events are immutable.'));
        static::deleting(fn () => throw new LogicException('Tracking-link audit events are immutable.'));
    }
}
