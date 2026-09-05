<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryWindowRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'delivery_window_starts_at' => ['nullable', 'date', 'required_with:delivery_window_ends_at'],
            'delivery_window_ends_at' => ['nullable', 'date', 'after:delivery_window_starts_at', 'required_with:delivery_window_starts_at'],
        ];
    }
}
