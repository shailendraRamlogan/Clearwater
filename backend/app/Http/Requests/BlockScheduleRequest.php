<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlockScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => 'required|date|date_format:Y-m-d',
            'time_slot_id' => 'nullable|uuid',
            'reason' => 'required|string|max:500',
        ];
    }
}
