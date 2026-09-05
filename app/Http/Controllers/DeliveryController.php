<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignDeliveryRequest;
use App\Http\Requests\StoreDeliveryRequest;
use App\Http\Requests\UpdateDeliveryStatusRequest;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryPersonnel;
use App\Services\DeliveryWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-operations');
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:unassigned,assigned,picked_up,in_transit,delivered,cancelled'],
            'personnel_id' => ['nullable', 'integer'],
            'scheduled_from' => ['nullable', 'date'],
            'scheduled_to' => ['nullable', 'date'],
        ]);
        $query = Delivery::query()->with(['customer', 'deliveryPersonnel']);

        if (filled($filters['q'] ?? null)) {
            $query->where(function ($query) use ($filters): void {
                $query->where('reference', 'like', '%'.$filters['q'].'%')
                    ->orWhere('pickup_address', 'like', '%'.$filters['q'].'%')
                    ->orWhere('dropoff_address', 'like', '%'.$filters['q'].'%')
                    ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', '%'.$filters['q'].'%'));
            });
        }

        foreach (['status', 'personnel_id'] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->where($field === 'personnel_id' ? 'delivery_personnel_id' : $field, $filters[$field]);
            }
        }

        foreach (['scheduled_from', 'scheduled_to'] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->whereDate('scheduled_at', $field === 'scheduled_from' ? '>=' : '<=', $filters[$field]);
            }
        }

        return view('tenant.deliveries.index', [
            'deliveries' => $query->latest()->paginate(20)->withQueryString(),
            'personnel' => DeliveryPersonnel::query()->where('status', DeliveryPersonnel::STATUS_ACTIVE)->orderBy('full_name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-operations');

        return view('tenant.deliveries.create', [
            'customers' => Customer::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreDeliveryRequest $request, DeliveryWorkflowService $workflow): RedirectResponse
    {
        $delivery = $workflow->create($request->user(), $request->validated());

        return redirect()->route('tenant.deliveries.show', $delivery)->with('status', 'Delivery created and ready for assignment.');
    }

    public function show(Delivery $delivery): View
    {
        Gate::authorize('manage-operations');
        $this->ensureCurrentCompanyOwns($delivery);

        return view('tenant.deliveries.show', [
            'delivery' => $delivery->load(['customer', 'deliveryPersonnel', 'statusHistory.actor', 'exceptions.events.actor', 'trackingLink', 'proof.files', 'proof.submittedBy', 'trackingNotifications.createdBy']),
            'personnel' => DeliveryPersonnel::query()->where('status', DeliveryPersonnel::STATUS_ACTIVE)->orderBy('full_name')->get(),
            'statuses' => Delivery::statuses(),
        ]);
    }

    public function assign(AssignDeliveryRequest $request, Delivery $delivery, DeliveryWorkflowService $workflow): RedirectResponse
    {
        $this->ensureCurrentCompanyOwns($delivery);
        $personnel = DeliveryPersonnel::query()->findOrFail($request->integer('delivery_personnel_id'));
        $workflow->assign($delivery, $personnel, $request->user());

        return back()->with('status', 'Delivery assignment updated.');
    }

    public function updateStatus(UpdateDeliveryStatusRequest $request, Delivery $delivery, DeliveryWorkflowService $workflow): RedirectResponse
    {
        Gate::authorize('manage-operations');
        $this->ensureCurrentCompanyOwns($delivery);
        $workflow->transition($delivery, $request->user(), $request->string('status')->toString(), $request->input('notes'));

        return back()->with('status', 'Delivery status updated.');
    }

    private function ensureCurrentCompanyOwns(Delivery $delivery): void
    {
        abort_unless($delivery->company_id === auth()->user()?->company_id, 404);
    }
}
