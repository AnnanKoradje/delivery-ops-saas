<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDeliveryStatusRequest;
use App\Models\Delivery;
use App\Models\DeliveryPersonnel;
use App\Services\DeliveryWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssignedDeliveryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('view-assigned-operations');

        $personnel = DeliveryPersonnel::query()->where('user_id', auth()->id())->firstOrFail();

        return view('tenant.assigned-deliveries.index', [
            'deliveries' => Delivery::query()
                ->where('delivery_personnel_id', $personnel->getKey())
                ->whereNotIn('status', [Delivery::STATUS_DELIVERED, Delivery::STATUS_CANCELLED])
                ->with(['customer', 'proof'])
                ->orderBy('scheduled_at')
                ->get(),
            'statuses' => Delivery::statuses(),
        ]);
    }

    public function updateStatus(UpdateDeliveryStatusRequest $request, Delivery $delivery, DeliveryWorkflowService $workflow): RedirectResponse
    {
        Gate::authorize('update-assigned-operations');
        abort_unless($delivery->company_id === $request->user()->company_id, 404);

        if ($request->string('status')->toString() === Delivery::STATUS_DELIVERED) {
            throw ValidationException::withMessages(['status' => 'Submit proof of delivery to complete this delivery.']);
        }

        $workflow->transition($delivery, $request->user(), $request->string('status')->toString(), $request->input('notes'), true);

        return back()->with('status', 'Delivery status updated.');
    }
}
