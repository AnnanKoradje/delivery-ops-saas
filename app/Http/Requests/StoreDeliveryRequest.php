<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-operations');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer'],
            'reference' => ['nullable', 'string', 'max:48'],
            'pickup_contact_name' => ['nullable', 'string', 'max:255'],
            'pickup_contact_phone' => ['nullable', 'string', 'max:64'],
            'pickup_address' => ['required', 'string', 'max:4000'],
            'dropoff_contact_name' => ['nullable', 'string', 'max:255'],
            'dropoff_contact_phone' => ['nullable', 'string', 'max:64'],
            'dropoff_address' => ['required', 'string', 'max:4000'],
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
