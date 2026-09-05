<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteDeliveryWithProofRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'photos' => ['nullable', 'array', 'max:3', 'required_without:signature'],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'signature' => ['nullable', 'file', 'image', 'mimes:png', 'max:1024', 'required_without:photos'],
        ];
    }
}
