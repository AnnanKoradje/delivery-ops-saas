<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['company_id', 'delivery_id', 'submitted_by_user_id', 'recipient_name', 'notes', 'submitted_at'])]
class DeliveryProof extends Model
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Delivery proofs are immutable.'));
        static::deleting(fn () => throw new LogicException('Delivery proofs are immutable.'));
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DeliveryProofFile::class);
    }
}
