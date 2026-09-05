<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptCustomerPortalInvitationRequest;
use App\Services\CustomerPortalOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CustomerPortalInvitationController extends Controller
{
    public function show(string $token, CustomerPortalOnboardingService $onboarding): View
    {
        return view('portal.auth.accept-invitation', ['invitation' => $onboarding->invitationForToken($token), 'token' => $token]);
    }

    public function store(string $token, AcceptCustomerPortalInvitationRequest $request, CustomerPortalOnboardingService $onboarding): RedirectResponse
    {
        $account = $onboarding->accept($token, $request->string('password')->toString());
        Auth::guard('customer')->login($account);
        $request->session()->regenerate();

        return redirect()->route('portal.deliveries.index')->with('status', 'Your customer portal account is ready.');
    }
}
