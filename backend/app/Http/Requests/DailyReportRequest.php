<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DailyReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => 'required|date|date_format:Y-m-d',
        ];
    }
}
