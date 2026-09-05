<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Services\DeliveryTrackingEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DeliveryTrackingEmailController extends Controller
{
    public function store(Delivery $delivery, DeliveryTrackingEmailService $emails): RedirectResponse
    {
        Gate::authorize('manage-operations');
        abort_unless($delivery->company_id === auth()->user()?->company_id, 404);
        $notification = $emails->send($delivery, auth()->user());

        return back()->with('status', match ($notification->status) {
            'previewed' => 'Local email preview recorded. Configure a verified platform sender before live delivery.',
            'sent' => 'Tracking email sent to the customer.',
            default => 'The email could not be sent. Check sender configuration before retrying.',
        });
    }
}
