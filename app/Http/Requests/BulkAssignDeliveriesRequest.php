<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class BulkAssignDeliveriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-operations');
    }

    public function rules(): array
    {
        return [
            'delivery_ids' => ['required', 'array', 'min:1', 'max:100'],
            'delivery_ids.*' => ['required', 'integer', 'distinct'],
            'delivery_personnel_id' => ['required', 'integer'],
        ];
    }
}
