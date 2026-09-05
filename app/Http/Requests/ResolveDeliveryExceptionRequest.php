<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ResolveDeliveryExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-operations');
    }

    public function rules(): array
    {
        return ['notes' => ['nullable', 'string', 'max:4000']];
    }
}
