<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffInvitationRequest;
use App\Models\Role;
use App\Models\StaffInvitation;
use App\Models\User;
use App\Services\StaffOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-company-staff');
        $company = $request->user()->company;
        $staff = User::query()->where('company_id', $company->id)->with('roles')->orderBy('name')->get();
        $roles = Role::query()->where('scope', Role::COMPANY_SCOPE)->orderBy('name')->get();
        $invitations = StaffInvitation::query()->where('company_id', $company->id)->latest()->limit(8)->get();

        return view('tenant.staff.index', compact('company', 'staff', 'roles', 'invitations'));
    }

    public function store(StoreStaffInvitationRequest $request, StaffOnboardingService $staffOnboarding): RedirectResponse
    {
        Gate::authorize('manage-company-staff');
        $token = $staffOnboarding->invite($request->user(), $request->validated());

        return back()->with('staff_invitation_url', route('tenant.staff-invitation.show', $token));
    }

    public function activate(Request $request, User $staff): RedirectResponse
    {
        return $this->updateActiveState($request, $staff, true);
    }

    public function deactivate(Request $request, User $staff): RedirectResponse
    {
        return $this->updateActiveState($request, $staff, false);
    }

    private function updateActiveState(Request $request, User $staff, bool $isActive): RedirectResponse
    {
        Gate::authorize('manage-company-staff');
        abort_unless((int) $staff->company_id === (int) $request->user()->company_id, 404);
        if ((int) $staff->id === (int) $request->user()->id) {
            throw ValidationException::withMessages(['staff' => 'You cannot change the active status of your own account.']);
        }
        $staff->update(['is_active' => $isActive]);

        return back()->with('status', $isActive ? 'Staff account activated.' : 'Staff account deactivated.');
    }
}
