<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyApplicationRequest;
use App\Models\Company;
use App\Services\CompanyOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PlatformCompanyController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-platform-companies');
        $status = $request->string('status')->toString();
        $companies = Company::query()->when(in_array($status, [Company::STATUS_PENDING, Company::STATUS_ACTIVE, Company::STATUS_REJECTED, Company::STATUS_SUSPENDED], true), fn ($query) => $query->where('status', $status))->latest()->paginate(15)->withQueryString();

        return view('platform.companies.index', compact('companies', 'status'));
    }

    public function create(): View
    {
        Gate::authorize('manage-platform-companies');

        return view('platform.companies.create');
    }

    public function store(StoreCompanyApplicationRequest $request, CompanyOnboardingService $onboarding): RedirectResponse
    {
        Gate::authorize('manage-platform-companies');
        $company = $onboarding->createManualCompany($request->validated(), $request->user());

        return redirect()->route('platform.companies.show', $company)->with('status', 'Pending company record created. Review and approve when ready.');
    }

    public function show(Company $company): View
    {
        Gate::authorize('manage-platform-companies');
        $company->load(['reviewEvents.actor', 'invitations' => fn ($query) => $query->latest()->limit(5)]);

        return view('platform.companies.show', compact('company'));
    }

    public function approve(Company $company, CompanyOnboardingService $onboarding): RedirectResponse
    {
        Gate::authorize('manage-platform-companies');
        $token = $onboarding->approve($company, request()->user());

        return redirect()->route('platform.companies.show', $company)->with('admin_invitation_url', route('onboarding.invitation.show', $token));
    }

    public function reject(Company $company, Request $request, CompanyOnboardingService $onboarding): RedirectResponse
    {
        Gate::authorize('manage-platform-companies');
        $onboarding->reject($company, $request->user(), $request->string('note')->trim()->toString() ?: null);

        return redirect()->route('platform.companies.show', $company)->with('status', 'Company application rejected.');
    }

    public function suspend(Company $company, Request $request, CompanyOnboardingService $onboarding): RedirectResponse
    {
        Gate::authorize('manage-platform-companies');
        $onboarding->suspend($company, $request->user(), $request->string('note')->trim()->toString() ?: null);

        return redirect()->route('platform.companies.show', $company)->with('status', 'Company workspace suspended.');
    }

    public function reactivate(Company $company, Request $request, CompanyOnboardingService $onboarding): RedirectResponse
    {
        Gate::authorize('manage-platform-companies');
        $onboarding->reactivate($company, $request->user(), $request->string('note')->trim()->toString() ?: null);

        return redirect()->route('platform.companies.show', $company)->with('status', 'Company workspace reactivated.');
    }
}
