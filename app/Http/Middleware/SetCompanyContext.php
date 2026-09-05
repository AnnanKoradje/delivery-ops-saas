<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(CompanyContext::class);
        $context->setCompany($request->user()?->company);

        try {
            return $next($request);
        } finally {
            $context->clear();
        }
    }
}
