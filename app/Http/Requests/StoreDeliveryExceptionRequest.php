<?php

namespace App\Http\Requests;

use App\Models\DeliveryException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreDeliveryExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-operations');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(DeliveryException::types()))],
            'description' => ['required', 'string', 'max:4000'],
        ];
    }
}
