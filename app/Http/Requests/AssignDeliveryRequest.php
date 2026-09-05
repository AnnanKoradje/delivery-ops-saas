<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AssignDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-operations');
    }

    public function rules(): array
    {
        return [
            'delivery_personnel_id' => ['required', 'integer'],
        ];
    }
}
