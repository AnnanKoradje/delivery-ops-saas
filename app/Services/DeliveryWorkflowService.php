<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryException;
use App\Models\DeliveryExceptionEvent;
use App\Models\DeliveryPersonnel;
use App\Models\DeliveryStatusHistory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeliveryWorkflowService
{
    public function create(User $actor, array $attributes): Delivery
    {
        $customer = Customer::query()->findOrFail($attributes['customer_id']);

        return DB::transaction(function () use ($actor, $attributes, $customer): Delivery {
            $reference = filled($attributes['reference'] ?? null)
                ? Str::upper($attributes['reference'])
                : 'DLV-'.now()->format('ymd').'-'.Str::upper(Str::random(6));

            if (Delivery::query()->where('reference', $reference)->exists()) {
                throw ValidationException::withMessages(['reference' => 'This delivery reference is already in use.']);
            }

            $delivery = Delivery::query()->create([
                ...$attributes,
                'customer_id' => $customer->getKey(),
                'reference' => $reference,
                'status' => Delivery::STATUS_UNASSIGNED,
                'created_by_user_id' => $actor->getKey(),
            ]);

            DeliveryStatusHistory::query()->create([
                'delivery_id' => $delivery->getKey(),
                'actor_user_id' => $actor->getKey(),
                'from_status' => null,
                'to_status' => Delivery::STATUS_UNASSIGNED,
                'notes' => 'Delivery created.',
            ]);

            return $delivery;
        });
    }

    public function assign(Delivery $delivery, DeliveryPersonnel $personnel, User $actor): Delivery
    {
        if ($delivery->company_id !== $personnel->company_id) {
            throw new AuthorizationException('Delivery personnel must belong to the current company.');
        }

        if ($personnel->status !== DeliveryPersonnel::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['delivery_personnel_id' => 'Only active delivery personnel can be assigned.']);
        }

        if (! in_array($delivery->status, [Delivery::STATUS_UNASSIGNED, Delivery::STATUS_ASSIGNED], true)) {
            throw ValidationException::withMessages(['delivery_personnel_id' => 'Only unassigned or assigned deliveries can be allocated.']);
        }

        return DB::transaction(function () use ($delivery, $personnel, $actor): Delivery {
            $fromStatus = $delivery->status;
            $delivery->update([
                'delivery_personnel_id' => $personnel->getKey(),
                'assigned_at' => now(),
                'status' => Delivery::STATUS_ASSIGNED,
            ]);

            DeliveryStatusHistory::query()->create([
                'delivery_id' => $delivery->getKey(),
                'actor_user_id' => $actor->getKey(),
                'from_status' => $fromStatus,
                'to_status' => Delivery::STATUS_ASSIGNED,
                'notes' => 'Assigned to '.$personnel->full_name.'.',
            ]);

            return $delivery->refresh();
        });
    }

    /** @param array<int, int> $deliveryIds */
    public function bulkAssign(array $deliveryIds, DeliveryPersonnel $personnel, User $actor): int
    {
        if ($personnel->status !== DeliveryPersonnel::STATUS_ACTIVE || $personnel->company_id !== $actor->company_id) {
            throw ValidationException::withMessages(['delivery_personnel_id' => 'Select an active delivery person from your company.']);
        }

        return DB::transaction(function () use ($deliveryIds, $personnel, $actor): int {
            $deliveries = Delivery::query()->whereIn('id', $deliveryIds)->lockForUpdate()->get();

            if ($deliveries->count() !== count($deliveryIds)) {
                throw new AuthorizationException('One or more deliveries were not found in the current company.');
            }

            foreach ($deliveries as $delivery) {
                $this->assign($delivery, $personnel, $actor);
            }

            return $deliveries->count();
        });
    }

    public function reportException(Delivery $delivery, User $actor, string $type, string $description): DeliveryException
    {
        if ($delivery->company_id !== $actor->company_id) {
            throw new AuthorizationException('The delivery must belong to the current company.');
        }

        return DB::transaction(function () use ($delivery, $actor, $type, $description): DeliveryException {
            $exception = DeliveryException::query()->create([
                'delivery_id' => $delivery->getKey(),
                'reported_by_user_id' => $actor->getKey(),
                'type' => $type,
                'description' => $description,
                'status' => DeliveryException::STATUS_OPEN,
            ]);

            DeliveryExceptionEvent::query()->create([
                'delivery_exception_id' => $exception->getKey(),
                'actor_user_id' => $actor->getKey(),
                'event_type' => 'reported',
                'notes' => $description,
            ]);

            return $exception;
        });
    }

    public function resolveException(DeliveryException $exception, User $actor, ?string $notes = null): DeliveryException
    {
        if ($exception->company_id !== $actor->company_id) {
            throw new AuthorizationException('The exception must belong to the current company.');
        }

        if ($exception->status === DeliveryException::STATUS_RESOLVED) {
            throw ValidationException::withMessages(['notes' => 'This exception is already resolved.']);
        }

        return DB::transaction(function () use ($exception, $actor, $notes): DeliveryException {
            $exception->update([
                'status' => DeliveryException::STATUS_RESOLVED,
                'resolved_at' => now(),
                'resolved_by_user_id' => $actor->getKey(),
            ]);
            DeliveryExceptionEvent::query()->create([
                'delivery_exception_id' => $exception->getKey(),
                'actor_user_id' => $actor->getKey(),
                'event_type' => 'resolved',
                'notes' => $notes,
            ]);

            return $exception->refresh();
        });
    }

    public function transition(Delivery $delivery, User $actor, string $toStatus, ?string $notes = null, bool $mustBeAssignee = false): Delivery
    {
        if ($mustBeAssignee) {
            $personnel = DeliveryPersonnel::query()->where('user_id', $actor->getKey())->first();

            if (! $personnel || $delivery->delivery_personnel_id !== $personnel->getKey()) {
                throw new AuthorizationException('Only the assigned delivery person can update this delivery.');
            }

            if (! in_array($toStatus, [Delivery::STATUS_PICKED_UP, Delivery::STATUS_IN_TRANSIT, Delivery::STATUS_DELIVERED], true)) {
                throw new AuthorizationException('Assigned delivery personnel can only record delivery-progress statuses.');
            }
        }

        $transitions = [
            Delivery::STATUS_UNASSIGNED => [Delivery::STATUS_CANCELLED],
            Delivery::STATUS_ASSIGNED => [Delivery::STATUS_PICKED_UP, Delivery::STATUS_CANCELLED],
            Delivery::STATUS_PICKED_UP => [Delivery::STATUS_IN_TRANSIT, Delivery::STATUS_CANCELLED],
            Delivery::STATUS_IN_TRANSIT => [Delivery::STATUS_DELIVERED],
            Delivery::STATUS_DELIVERED => [],
            Delivery::STATUS_CANCELLED => [],
        ];

        if (! in_array($toStatus, $transitions[$delivery->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'This delivery cannot transition from '.$delivery->status.' to '.$toStatus.'.']);
        }

        return DB::transaction(function () use ($delivery, $actor, $toStatus, $notes): Delivery {
            $fromStatus = $delivery->status;
            $delivery->update([
                'status' => $toStatus,
                'completed_at' => $toStatus === Delivery::STATUS_DELIVERED ? now() : null,
            ]);

            DeliveryStatusHistory::query()->create([
                'delivery_id' => $delivery->getKey(),
                'actor_user_id' => $actor->getKey(),
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'notes' => $notes,
            ]);

            return $delivery->refresh();
        });
    }
}
