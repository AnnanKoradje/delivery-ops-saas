<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'delivery_id', 'delivery_tracking_link_id', 'created_by_user_id', 'channel', 'recipient_email', 'reply_to_email', 'subject', 'status', 'provider_message_id', 'failure_reason', 'sent_at'])]
class DeliveryTrackingNotification extends Model
{
    use BelongsToCompany;

    public const CHANNEL_EMAIL = 'email';

    public const STATUS_PREVIEWED = 'previewed';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function trackingLink(): BelongsTo
    {
        return $this->belongsTo(DeliveryTrackingLink::class, 'delivery_tracking_link_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
