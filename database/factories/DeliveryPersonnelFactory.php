<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\DeliveryPersonnel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeliveryPersonnel> */
class DeliveryPersonnelFactory extends Factory
{
    protected $model = DeliveryPersonnel::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'employee_code' => strtoupper(fake()->bothify('DRV-###?')),
            'status' => DeliveryPersonnel::STATUS_ACTIVE,
        ];
    }
}
