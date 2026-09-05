<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteDeliveryWithProofRequest;
use App\Models\Delivery;
use App\Models\DeliveryPersonnel;
use App\Models\DeliveryProofFile;
use App\Services\DeliveryProofService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeliveryProofController extends Controller
{
    public function store(CompleteDeliveryWithProofRequest $request, Delivery $delivery, DeliveryProofService $proofs): RedirectResponse
    {
        Gate::authorize('update-assigned-operations');
        abort_unless($delivery->company_id === $request->user()->company_id, 404);
        $proofs->complete(
            $delivery,
            $request->user(),
            $request->file('photos', []),
            $request->file('signature'),
            $request->input('recipient_name'),
            $request->input('notes'),
        );

        return back()->with('status', 'Proof of delivery saved and delivery marked delivered.');
    }

    public function show(DeliveryProofFile $deliveryProofFile): StreamedResponse
    {
        $proof = $deliveryProofFile->proof()->with('delivery')->firstOrFail();
        abort_unless($proof->company_id === auth()->user()?->company_id, 404);

        if (! Gate::allows('manage-operations')) {
            Gate::authorize('view-assigned-operations');
            $personnel = DeliveryPersonnel::query()->where('user_id', auth()->id())->first();
            abort_unless($personnel && $proof->delivery->delivery_personnel_id === $personnel->getKey(), 404);
        }

        abort_unless(Storage::disk($deliveryProofFile->disk)->exists($deliveryProofFile->path), 404);

        return Storage::disk($deliveryProofFile->disk)->response($deliveryProofFile->path, $deliveryProofFile->original_filename, ['Content-Type' => $deliveryProofFile->mime_type, 'Content-Disposition' => 'inline']);
    }
}
