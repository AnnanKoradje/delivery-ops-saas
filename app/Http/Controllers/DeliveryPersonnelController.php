<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryPersonnelRequest;
use App\Models\DeliveryPersonnel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryPersonnelController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-delivery-personnel');
        $personnel = DeliveryPersonnel::query()->with('user')->orderBy('full_name')->get();

        return view('tenant.personnel.index', compact('personnel'));
    }

    public function store(StoreDeliveryPersonnelRequest $request): RedirectResponse
    {
        Gate::authorize('manage-delivery-personnel');
        DeliveryPersonnel::query()->create($request->validated());

        return back()->with('status', 'Delivery personnel record added.');
    }

    public function updateStatus(Request $request, DeliveryPersonnel $deliveryPersonnel): RedirectResponse
    {
        Gate::authorize('manage-delivery-personnel');
        abort_unless((int) $deliveryPersonnel->company_id === (int) $request->user()->company_id, 404);
        $validated = $request->validate(['status' => ['required', Rule::in([DeliveryPersonnel::STATUS_ACTIVE, DeliveryPersonnel::STATUS_INACTIVE])]]);
        $deliveryPersonnel->update($validated);

        return back()->with('status', 'Delivery personnel status updated.');
    }
}
