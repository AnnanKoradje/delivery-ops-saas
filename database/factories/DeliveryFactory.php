<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Delivery;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Delivery> */
class DeliveryFactory extends Factory
{
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'customer_id' => Customer::factory()->for($company),
            'reference' => 'DLV-'.Str::upper(Str::random(8)),
            'status' => Delivery::STATUS_UNASSIGNED,
            'pickup_contact_name' => fake()->name(),
            'pickup_contact_phone' => fake()->phoneNumber(),
            'pickup_address' => fake()->address(),
            'dropoff_contact_name' => fake()->name(),
            'dropoff_contact_phone' => fake()->phoneNumber(),
            'dropoff_address' => fake()->address(),
            'scheduled_at' => now()->addDay(),
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->getKey(),
            'customer_id' => Customer::factory()->for($company),
        ]);
    }
}
