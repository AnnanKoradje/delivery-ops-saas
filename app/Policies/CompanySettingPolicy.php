<?php

namespace App\Policies;

use App\Models\CompanySetting;
use App\Models\User;

class CompanySettingPolicy
{
    public function view(User $user, CompanySetting $setting): bool
    {
        return ! $user->isSuperAdmin()
            && $user->company_id === $setting->company_id
            && $user->hasPermission('company.settings.view');
    }

    public function update(User $user, CompanySetting $setting): bool
    {
        return ! $user->isSuperAdmin()
            && $user->company_id === $setting->company_id
            && $user->hasPermission('company.settings.manage');
    }
}
