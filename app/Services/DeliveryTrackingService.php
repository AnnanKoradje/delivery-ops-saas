<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Delivery;
use App\Models\DeliveryTrackingLink;
use App\Models\DeliveryTrackingLinkEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeliveryTrackingService
{
    /** @return array{DeliveryTrackingLink, string} */
    public function createOrRotate(Delivery $delivery, User $actor): array
    {
        $this->assertOwnedDelivery($delivery, $actor);

        return DB::transaction(function () use ($delivery, $actor): array {
            $token = Str::random(80);
            $link = DeliveryTrackingLink::query()->firstOrNew(['delivery_id' => $delivery->getKey()]);
            $wasExisting = $link->exists;
            $link->fill([
                'company_id' => $delivery->company_id,
                'created_by_user_id' => $wasExisting ? $link->created_by_user_id : $actor->getKey(),
                'token_hash' => hash('sha256', $token),
                'revoked_at' => null,
                'revoked_by_user_id' => null,
            ])->save();
            DeliveryTrackingLinkEvent::query()->create([
                'company_id' => $delivery->company_id,
                'delivery_tracking_link_id' => $link->getKey(),
                'actor_user_id' => $actor->getKey(),
                'event_type' => $wasExisting ? 'rotated' : 'created',
            ]);

            return [$link->refresh(), $token];
        });
    }

    public function revoke(DeliveryTrackingLink $link, User $actor): DeliveryTrackingLink
    {
        if ($link->company_id !== $actor->company_id) {
            throw new AuthorizationException('The tracking link must belong to the current company.');
        }

        if (! $link->isActive()) {
            throw ValidationException::withMessages(['tracking_link' => 'This public tracking link is already revoked.']);
        }

        return DB::transaction(function () use ($link, $actor): DeliveryTrackingLink {
            $link->update(['revoked_at' => now(), 'revoked_by_user_id' => $actor->getKey()]);
            DeliveryTrackingLinkEvent::query()->create([
                'company_id' => $link->company_id,
                'delivery_tracking_link_id' => $link->getKey(),
                'actor_user_id' => $actor->getKey(),
                'event_type' => 'revoked',
            ]);

            return $link->refresh();
        });
    }

    public function updateWindow(Delivery $delivery, User $actor, array $attributes): Delivery
    {
        $this->assertOwnedDelivery($delivery, $actor);
        $delivery->update($attributes);

        return $delivery->refresh();
    }

    public function findPubliclyTrackableDelivery(string $token): ?Delivery
    {
        $link = DeliveryTrackingLink::query()->withoutGlobalScopes()
            ->where('token_hash', hash('sha256', $token))
            ->with(['delivery.statusHistory' => fn ($query) => $query->oldest('created_at'), 'delivery.company'])
            ->first();

        if (! $link || ! $link->isActive() || ! $link->delivery || $link->delivery->company_id !== $link->company_id || $link->delivery->company?->status !== Company::STATUS_ACTIVE) {
            return null;
        }
        $link->forceFill(['last_accessed_at' => now()])->save();

        return $link->delivery;
    }

    /** @return array{label: string, detail: string} */
    public function publicProgress(Delivery $delivery): array
    {
        return match ($delivery->status) {
            Delivery::STATUS_UNASSIGNED => ['label' => 'Preparing your delivery', 'detail' => 'The delivery company is preparing this order.'],
            Delivery::STATUS_ASSIGNED => ['label' => 'Assigned for pickup', 'detail' => 'A delivery person has been assigned.'],
            Delivery::STATUS_PICKED_UP => ['label' => 'Collected', 'detail' => 'Your order has been collected and is moving through the delivery process.'],
            Delivery::STATUS_IN_TRANSIT => ['label' => 'On the way', 'detail' => 'Your order is on the way to its destination.'],
            Delivery::STATUS_DELIVERED => ['label' => 'Delivered', 'detail' => 'This delivery has been completed.'],
            Delivery::STATUS_CANCELLED => ['label' => 'Cancelled', 'detail' => 'This delivery was cancelled. Contact the delivery company for assistance.'],
        };
    }

    private function assertOwnedDelivery(Delivery $delivery, User $actor): void
    {
        if ($delivery->company_id !== $actor->company_id) {
            throw new AuthorizationException('The delivery must belong to the current company.');
        }
    }
}
