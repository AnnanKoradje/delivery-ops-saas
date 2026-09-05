<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CompanyWorkspaceController extends Controller
{
    public function edit(Request $request): View
    {
        $company = $request->user()->company;
        Gate::authorize('update', $company);

        return view('tenant.profile.edit', compact('company'));
    }

    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        $company = $request->user()->company;
        Gate::authorize('update', $company);
        $company->update($request->validated());

        return back()->with('status', 'Company profile updated.');
    }
}
