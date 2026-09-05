<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackDeliveryRequest;
use App\Models\Delivery;
use App\Services\DeliveryTrackingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class PublicTrackingController extends Controller
{
    public function create(): View
    {
        return view('tracking.lookup');
    }

    public function lookup(TrackDeliveryRequest $request): RedirectResponse
    {
        return redirect()->route('tracking.show', $request->string('token')->toString());
    }

    public function show(string $token, DeliveryTrackingService $tracking): View|Response
    {
        $delivery = $tracking->findPubliclyTrackableDelivery($token);

        if (! $delivery) {
            return response()->view('tracking.unavailable', [], 404);
        }

        return view('tracking.show', [
            'delivery' => $delivery,
            'progress' => $tracking->publicProgress($delivery),
            'statuses' => Delivery::statuses(),
        ]);
    }
}
