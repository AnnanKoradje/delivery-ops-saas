<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['company_id', 'delivery_proof_id', 'kind', 'disk', 'path', 'original_filename', 'mime_type', 'size_bytes', 'sha256'])]
class DeliveryProofFile extends Model
{
    use BelongsToCompany;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Delivery proof files are immutable.'));
        static::deleting(fn () => throw new LogicException('Delivery proof files are immutable.'));
    }

    public function proof(): BelongsTo
    {
        return $this->belongsTo(DeliveryProof::class, 'delivery_proof_id');
    }
}
