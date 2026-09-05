<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\Tenancy\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $context = app(CompanyContext::class);

        abort_unless(
            $user !== null
            && ! $user->isSuperAdmin()
            && $context->hasCompany()
            && $user->company?->status === Company::STATUS_ACTIVE,
            403,
        );

        return $next($request);
    }
}
