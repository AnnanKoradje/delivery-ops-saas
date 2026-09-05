<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptStaffInvitationRequest;
use App\Models\StaffInvitation;
use App\Services\StaffOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffInvitationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = $this->findUsableInvitation($token);

        return view('tenant.staff.accept-invitation', compact('invitation', 'token'));
    }

    public function store(string $token, AcceptStaffInvitationRequest $request, StaffOnboardingService $staffOnboarding): RedirectResponse
    {
        $user = $staffOnboarding->accept($this->findUsableInvitation($token), $request->validated());
        Auth::login($user);

        return redirect()->route('dashboard')->with('status', 'Your staff account is ready.');
    }

    private function findUsableInvitation(string $token): StaffInvitation
    {
        $invitation = StaffInvitation::query()->with(['company', 'role'])->where('token_hash', hash('sha256', $token))->firstOrFail();
        abort_unless($invitation->isUsable(), 410);

        return $invitation;
    }
}
