<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPrivateTourRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'confirmed_tour_date' => 'required|date|date_format:Y-m-d',
            'confirmed_time_slot_id' => 'required|uuid|exists:time_slots,id',
            'total_price_cents' => 'required|integer|min:0',
            'admin_notes' => 'nullable|string|max:1000',
            'guests' => 'required|array|min:1',
            'guests.*.first_name' => 'required|string|max:100',
            'guests.*.last_name' => 'required|string|max:100',
            'guests.*.email' => 'nullable|email|max:255',
            'guests.*.phone' => 'nullable|string|max:30',
            'guests.*.is_primary' => 'boolean',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $guests = $this->input('guests', []);
                $hasPrimary = false;
                foreach ($guests as $guest) {
                    if (!empty($guest['is_primary'])) {
                        $hasPrimary = true;
                        break;
                    }
                }
                if (!$hasPrimary) {
                    $validator->errors()->add('guests', 'At least one guest must be marked as primary.');
                }
            },
        ];
    }
}
