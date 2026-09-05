<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDeliveryWindowRequest;
use App\Models\Delivery;
use App\Models\DeliveryTrackingLink;
use App\Services\DeliveryTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DeliveryTrackingLinkController extends Controller
{
    public function createOrRotate(Delivery $delivery, DeliveryTrackingService $tracking): RedirectResponse
    {
        Gate::authorize('manage-operations');
        abort_unless($delivery->company_id === auth()->user()?->company_id, 404);
        [, $token] = $tracking->createOrRotate($delivery, auth()->user());

        return back()->with('tracking_link', route('tracking.show', $token));
    }

    public function revoke(DeliveryTrackingLink $trackingLink, DeliveryTrackingService $tracking): RedirectResponse
    {
        Gate::authorize('manage-operations');
        abort_unless($trackingLink->company_id === auth()->user()?->company_id, 404);
        $tracking->revoke($trackingLink, auth()->user());

        return back()->with('status', 'Public tracking link revoked.');
    }

    public function updateWindow(UpdateDeliveryWindowRequest $request, Delivery $delivery, DeliveryTrackingService $tracking): RedirectResponse
    {
        Gate::authorize('manage-operations');
        abort_unless($delivery->company_id === auth()->user()?->company_id, 404);
        $tracking->updateWindow($delivery, auth()->user(), $request->validated());

        return back()->with('status', 'Public delivery window updated.');
    }
}
