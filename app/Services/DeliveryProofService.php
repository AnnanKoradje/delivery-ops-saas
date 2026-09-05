<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DeliveryPersonnel;
use App\Models\DeliveryProof;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeliveryProofService
{
    /** @param array<int, UploadedFile> $photos */
    public function complete(Delivery $delivery, User $actor, array $photos, ?UploadedFile $signature, ?string $recipientName, ?string $notes): DeliveryProof
    {
        $personnel = DeliveryPersonnel::query()->where('user_id', $actor->getKey())->first();

        if (! $personnel || $personnel->company_id !== $delivery->company_id || $delivery->delivery_personnel_id !== $personnel->getKey()) {
            throw new AuthorizationException('Only the assigned delivery person can submit proof for this delivery.');
        }

        if ($delivery->status !== Delivery::STATUS_IN_TRANSIT) {
            throw ValidationException::withMessages(['status' => 'Proof can only complete an in-transit delivery.']);
        }

        if ($delivery->proof()->exists()) {
            throw ValidationException::withMessages(['proof' => 'Proof of delivery has already been submitted.']);
        }

        $stored = [];

        try {
            foreach ($photos as $photo) {
                $stored[] = $this->store($delivery, $photo, 'photo');
            }

            if ($signature) {
                $stored[] = $this->store($delivery, $signature, 'signature');
            }

            return DB::transaction(function () use ($delivery, $actor, $recipientName, $notes, $stored): DeliveryProof {
                $proof = DeliveryProof::query()->create([
                    'company_id' => $delivery->company_id,
                    'delivery_id' => $delivery->getKey(),
                    'submitted_by_user_id' => $actor->getKey(),
                    'recipient_name' => $recipientName,
                    'notes' => $notes,
                    'submitted_at' => now(),
                ]);

                foreach ($stored as $file) {
                    $proof->files()->create(['company_id' => $delivery->company_id, ...$file]);
                }

                app(DeliveryWorkflowService::class)->transition($delivery, $actor, Delivery::STATUS_DELIVERED, 'Proof of delivery submitted.', false);

                return $proof->load('files');
            });
        } catch (\Throwable $exception) {
            foreach ($stored as $file) {
                Storage::disk($file['disk'])->delete($file['path']);
            }

            throw $exception;
        }
    }

    /** @return array{kind: string, disk: string, path: string, original_filename: string, mime_type: string, size_bytes: int, sha256: string} */
    private function store(Delivery $delivery, UploadedFile $file, string $kind): array
    {
        $extension = strtolower($file->extension());
        $path = $file->storeAs('delivery-proofs/'.$delivery->company_id.'/'.$delivery->getKey(), Str::uuid().'.'.$extension, 'local');

        return [
            'kind' => $kind,
            'disk' => 'local',
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
        ];
    }
}
