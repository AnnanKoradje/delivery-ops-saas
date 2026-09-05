<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyApplicationRequest;
use App\Services\CompanyOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyApplicationController extends Controller
{
    public function create(): View
    {
        return view('onboarding.apply');
    }

    public function store(StoreCompanyApplicationRequest $request, CompanyOnboardingService $onboarding): RedirectResponse
    {
        $onboarding->submitApplication($request->validated());

        return redirect()->route('onboarding.application.submitted');
    }

    public function submitted(): View
    {
        return view('onboarding.submitted');
    }
}
