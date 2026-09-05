<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $email = config('platform.super_admin.email');
        $password = config('platform.super_admin.password');

        if (blank($email) || blank($password)) {
            return;
        }

        $superAdmin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'company_id' => null,
                'name' => config('platform.super_admin.name'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $superAdmin->assignRole(Role::query()->where('slug', 'super-admin')->firstOrFail());
    }
}
