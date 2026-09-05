<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            ['name' => 'View Platform Dashboard', 'slug' => 'platform.dashboard.view', 'description' => 'View platform-level company oversight data.'],
            ['name' => 'Manage Companies', 'slug' => 'platform.companies.manage', 'description' => 'Approve, suspend, and manage company records.'],
            ['name' => 'View Company Settings', 'slug' => 'company.settings.view', 'description' => 'View settings for the current company only.'],
            ['name' => 'Manage Company Settings', 'slug' => 'company.settings.manage', 'description' => 'Update settings for the current company only.'],
            ['name' => 'Manage Company Profile', 'slug' => 'company.profile.manage', 'description' => 'Update the current company profile.'],
            ['name' => 'Manage Staff', 'slug' => 'company.staff.manage', 'description' => 'Manage staff in the current company only.'],
            ['name' => 'Manage Delivery Personnel', 'slug' => 'company.personnel.manage', 'description' => 'Manage delivery personnel records in the current company only.'],
            ['name' => 'Manage Operations', 'slug' => 'operations.manage', 'description' => 'Create and manage operations in the current company only.'],
            ['name' => 'View Assigned Operations', 'slug' => 'operations.assigned.view', 'description' => 'View only assigned operations.'],
            ['name' => 'Update Assigned Operations', 'slug' => 'operations.assigned.update', 'description' => 'Update the operational status of assigned operations only.'],
        ])->mapWithKeys(function (array $attributes): array {
            $permission = Permission::query()->updateOrCreate(['slug' => $attributes['slug']], $attributes);

            return [$permission->slug => $permission->getKey()];
        });

        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'scope' => Role::PLATFORM_SCOPE, 'description' => 'Platform owner with company oversight only.', 'permissions' => ['platform.dashboard.view', 'platform.companies.manage']],
            ['name' => 'Company Admin', 'slug' => 'company-admin', 'scope' => Role::COMPANY_SCOPE, 'description' => 'Administrator for one company workspace.', 'permissions' => ['company.settings.view', 'company.settings.manage', 'company.profile.manage', 'company.staff.manage', 'company.personnel.manage', 'operations.manage']],
            ['name' => 'Dispatcher', 'slug' => 'dispatcher', 'scope' => Role::COMPANY_SCOPE, 'description' => 'Operations staff for one company workspace.', 'permissions' => ['operations.manage']],
            ['name' => 'Delivery Personnel', 'slug' => 'delivery-personnel', 'scope' => Role::COMPANY_SCOPE, 'description' => 'Delivery worker with assignment-only access.', 'permissions' => ['operations.assigned.view', 'operations.assigned.update']],
        ];

        foreach ($roles as $roleDefinition) {
            $permissionSlugs = $roleDefinition['permissions'];
            unset($roleDefinition['permissions']);
            $role = Role::query()->updateOrCreate(['slug' => $roleDefinition['slug']], $roleDefinition);
            $role->permissions()->sync($permissions->only($permissionSlugs)->values()->all());
        }
    }
}
