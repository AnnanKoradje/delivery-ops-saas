<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCustomerPortalProfileRequest;
use App\Models\Delivery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CustomerPortalController extends Controller
{
    public function index(): View
    {
        $account = auth('customer')->user();

        return view('portal.deliveries.index', [
            'deliveries' => Delivery::query()
                ->where('company_id', $account->company_id)
                ->where('customer_id', $account->customer_id)
                ->latest()
                ->paginate(20),
            'statuses' => Delivery::statuses(),
        ]);
    }

    public function show(Delivery $delivery): View
    {
        $account = auth('customer')->user();
        abort_unless($delivery->company_id === $account->company_id && $delivery->customer_id === $account->customer_id, 404);

        return view('portal.deliveries.show', [
            'delivery' => $delivery->load('statusHistory'),
            'statuses' => Delivery::statuses(),
        ]);
    }

    public function editProfile(): View
    {
        return view('portal.profile.edit', ['customer' => auth('customer')->user()->customer]);
    }

    public function updateProfile(UpdateCustomerPortalProfileRequest $request): RedirectResponse
    {
        $account = auth('customer')->user();
        $customer = $account->customer;
        abort_unless($customer->company_id === $account->company_id, 404);
        $customer->update($request->validated());

        return back()->with('status', 'Your profile has been updated.');
    }
}
