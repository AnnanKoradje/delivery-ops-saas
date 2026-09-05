<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Delivery;
use App\Models\DeliveryStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeliveryStatusHistory> */
class DeliveryStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'delivery_id' => Delivery::factory(),
            'from_status' => Delivery::STATUS_UNASSIGNED,
            'to_status' => Delivery::STATUS_ASSIGNED,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forDelivery(Delivery $delivery): static
    {
        return $this->state(fn (): array => [
            'company_id' => $delivery->company_id,
            'delivery_id' => $delivery->getKey(),
        ]);
    }
}
