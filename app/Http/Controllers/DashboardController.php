<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->isSuperAdmin()) {
            return redirect()->route('platform.dashboard');
        }

        abort_unless($request->user()->company !== null, 403);

        return match ($request->user()->company->status) {
            Company::STATUS_ACTIVE => view('tenant.access-active'),
            Company::STATUS_PENDING => view('tenant.access-pending'),
            Company::STATUS_SUSPENDED, Company::STATUS_REJECTED => view('tenant.access-restricted'),
            default => abort(403),
        };
    }
}
