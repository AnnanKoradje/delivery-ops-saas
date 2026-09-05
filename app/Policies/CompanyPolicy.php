<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function view(User $user, Company $company): bool
    {
        return $user->isSuperAdmin() || $user->company_id === $company->getKey();
    }

    public function update(User $user, Company $company): bool
    {
        return $user->company_id === $company->getKey()
            && $user->hasPermission('company.profile.manage');
    }
}
