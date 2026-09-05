<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'business_type' => ['nullable', 'string', 'max:80'], 'contact_name' => ['nullable', 'string', 'max:120'], 'contact_email' => ['nullable', 'email:rfc', 'max:320'], 'contact_phone' => ['nullable', 'string', 'max:40'], 'address' => ['nullable', 'string', 'max:2000']];
    }
}
