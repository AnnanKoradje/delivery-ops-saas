<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Tenancy\CompanyContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CompanyContext::class);
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::define('view-platform-dashboard', fn (User $user): bool => $user->isSuperAdmin()
            && $user->hasPermission('platform.dashboard.view'));
        Gate::define('manage-platform-companies', fn (User $user): bool => $user->isSuperAdmin()
            && $user->hasPermission('platform.companies.manage'));
        Gate::define('manage-company-staff', fn (User $user): bool => ! $user->isSuperAdmin()
            && $user->hasPermission('company.staff.manage'));
        Gate::define('manage-delivery-personnel', fn (User $user): bool => ! $user->isSuperAdmin()
            && $user->hasPermission('company.personnel.manage'));
        Gate::define('manage-operations', fn (User $user): bool => ! $user->isSuperAdmin()
            && $user->hasPermission('operations.manage'));
        Gate::define('view-assigned-operations', fn (User $user): bool => ! $user->isSuperAdmin()
            && $user->hasPermission('operations.assigned.view'));
        Gate::define('update-assigned-operations', fn (User $user): bool => ! $user->isSuperAdmin()
            && $user->hasPermission('operations.assigned.update'));
    }
}
