<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'public_id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'business_type' => fake()->randomElement(['Courier', 'Restaurant', 'Retail', 'Pharmacy']),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->unique()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'status' => Company::STATUS_PENDING,
            'onboarding_method' => 'self_registered',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => Company::STATUS_ACTIVE]);
    }
}
