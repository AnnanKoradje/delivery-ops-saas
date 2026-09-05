<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerPortalOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CustomerController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-operations');

        return view('tenant.customers.index', [
            'customers' => Customer::query()->latest()->paginate(20),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $attributes = $request->safe()->except('tracking_email_consent');

        if ($request->boolean('tracking_email_consent')) {
            $attributes['tracking_email_consent_at'] = now();
        }

        Customer::query()->create($attributes);

        return back()->with('status', 'Customer created.');
    }

    public function edit(Customer $customer): View
    {
        Gate::authorize('manage-operations');
        $this->ensureCurrentCompanyOwns($customer);

        return view('tenant.customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->ensureCurrentCompanyOwns($customer);
        $attributes = $request->safe()->except('tracking_email_consent');

        if ($request->boolean('tracking_email_consent')) {
            $attributes['tracking_email_consent_at'] = $customer->tracking_email_consent_at ?: now();
            $attributes['tracking_email_opted_out_at'] = null;
        } elseif ($customer->tracking_email_consent_at) {
            $attributes['tracking_email_consent_at'] = null;
            $attributes['tracking_email_opted_out_at'] = now();
        }

        $customer->update($attributes);

        return redirect()->route('tenant.customers.index')->with('status', 'Customer updated.');
    }

    public function inviteToPortal(Customer $customer, CustomerPortalOnboardingService $onboarding): RedirectResponse
    {
        Gate::authorize('manage-operations');
        $this->ensureCurrentCompanyOwns($customer);
        [, $token] = $onboarding->invite(auth()->user(), $customer);

        return back()->with('portal_invitation_link', route('portal.invitation.show', $token));
    }

    private function ensureCurrentCompanyOwns(Customer $customer): void
    {
        abort_unless($customer->company_id === auth()->user()?->company_id, 404);
    }
}
