<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryPersonnelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['full_name' => ['required', 'string', 'max:120'], 'email' => ['nullable', 'email:rfc', 'max:320'], 'phone' => ['nullable', 'string', 'max:40'], 'employee_code' => ['nullable', 'string', 'max:40'], 'notes' => ['nullable', 'string', 'max:2000']];
    }
}
