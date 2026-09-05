<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompanySetting> */
class CompanySettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'personnel_label_singular' => 'Driver',
            'personnel_label_plural' => 'Drivers',
            'operation_label_singular' => 'Delivery',
            'operation_label_plural' => 'Deliveries',
            'time_zone' => 'UTC',
            'tracking_enabled' => true,
        ];
    }
}
