<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptCompanyAdminInvitationRequest;
use App\Models\CompanyAdminInvitation;
use App\Services\CompanyOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CompanyAdminInvitationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = $this->findUsableInvitation($token);

        return view('onboarding.accept-invitation', compact('invitation', 'token'));
    }

    public function store(string $token, AcceptCompanyAdminInvitationRequest $request, CompanyOnboardingService $onboarding): RedirectResponse
    {
        $user = $onboarding->acceptInvitation($this->findUsableInvitation($token), $request->validated());
        Auth::login($user);

        return redirect()->route('dashboard')->with('status', 'Your Company Admin account is ready.');
    }

    private function findUsableInvitation(string $token): CompanyAdminInvitation
    {
        $invitation = CompanyAdminInvitation::query()->with('company')->where('token_hash', hash('sha256', $token))->firstOrFail();
        abort_unless($invitation->isUsable(), 410);

        return $invitation;
    }
}
