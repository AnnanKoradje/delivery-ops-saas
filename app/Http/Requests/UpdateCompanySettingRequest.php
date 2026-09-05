<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['personnel_label_singular' => ['required', 'string', 'max:60'], 'personnel_label_plural' => ['required', 'string', 'max:60'], 'operation_label_singular' => ['required', 'string', 'max:60'], 'operation_label_plural' => ['required', 'string', 'max:60'], 'time_zone' => ['required', 'timezone'], 'tracking_enabled' => ['nullable', 'boolean']];
    }
}
