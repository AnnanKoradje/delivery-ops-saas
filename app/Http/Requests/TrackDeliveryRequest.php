<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackDeliveryRequest extends FormRequest
{
    public function rules(): array
    {
        return ['token' => ['required', 'string', 'min:40', 'max:128']];
    }
}
