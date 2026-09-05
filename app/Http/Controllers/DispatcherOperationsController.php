<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkAssignDeliveriesRequest;
use App\Http\Requests\CancelDeliveryRequest;
use App\Http\Requests\ResolveDeliveryExceptionRequest;
use App\Http\Requests\StoreDeliveryExceptionRequest;
use App\Models\Delivery;
use App\Models\DeliveryException;
use App\Models\DeliveryPersonnel;
use App\Services\DeliveryWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DispatcherOperationsController extends Controller
{
    public function bulkAssign(BulkAssignDeliveriesRequest $request, DeliveryWorkflowService $workflow): RedirectResponse
    {
        $personnel = DeliveryPersonnel::query()->findOrFail($request->integer('delivery_personnel_id'));
        $count = $workflow->bulkAssign($request->validated('delivery_ids'), $personnel, $request->user());

        return back()->with('status', $count.' delivery '.str('assignment')->plural($count).' updated.');
    }

    public function cancel(CancelDeliveryRequest $request, Delivery $delivery, DeliveryWorkflowService $workflow): RedirectResponse
    {
        Gate::authorize('manage-operations');
        $this->ensureCurrentCompanyOwns($delivery);
        $workflow->transition($delivery, $request->user(), Delivery::STATUS_CANCELLED, $request->string('notes')->toString());

        return back()->with('status', 'Delivery cancelled and recorded in its history.');
    }

    public function storeException(StoreDeliveryExceptionRequest $request, Delivery $delivery, DeliveryWorkflowService $workflow): RedirectResponse
    {
        Gate::authorize('manage-operations');
        $this->ensureCurrentCompanyOwns($delivery);
        $workflow->reportException($delivery, $request->user(), $request->string('type')->toString(), $request->string('description')->toString());

        return back()->with('status', 'Operational exception recorded.');
    }

    public function resolveException(ResolveDeliveryExceptionRequest $request, DeliveryException $exception, DeliveryWorkflowService $workflow): RedirectResponse
    {
        Gate::authorize('manage-operations');
        abort_unless($exception->company_id === $request->user()->company_id, 404);
        $workflow->resolveException($exception, $request->user(), $request->input('notes'));

        return back()->with('status', 'Operational exception resolved.');
    }

    public function reports(): View
    {
        Gate::authorize('manage-operations');

        return view('tenant.operations.reports', [
            'statusCounts' => Delivery::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status'),
            'openExceptionCount' => DeliveryException::query()->where('status', DeliveryException::STATUS_OPEN)->count(),
            'scheduledTodayCount' => Delivery::query()->whereDate('scheduled_at', today())->count(),
        ]);
    }

    private function ensureCurrentCompanyOwns(Delivery $delivery): void
    {
        abort_unless($delivery->company_id === auth()->user()?->company_id, 404);
    }
}
