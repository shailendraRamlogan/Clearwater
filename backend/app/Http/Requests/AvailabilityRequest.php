<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvailabilityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => 'required|date|date_format:Y-m-d|after_or_equal:today',
        ];
    }
}
