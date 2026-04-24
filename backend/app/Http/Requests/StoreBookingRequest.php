<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tour_date' => 'required|date|date_format:Y-m-d|after_or_equal:today',
            'time_slot_id' => 'required|uuid',
            'adult_count' => 'required|integer|min:1|max:50',
            'child_count' => 'required|integer|min:0|max:50',
            'package_upgrade' => 'boolean',
            'special_occasion' => 'boolean',
            'special_comment' => 'nullable|string|max:500',
            'guest' => 'required|array',
            'guest.first_name' => 'required|string|max:100',
            'guest.last_name' => 'required|string|max:100',
            'guest.email' => 'required|email|max:255',
            'guest.phone' => 'required|string|max:30',
            'guests' => 'nullable|array',
            'guests.*.first_name' => 'nullable|string|max:100',
            'guests.*.last_name' => 'nullable|string|max:100',
            'guests.*.email' => 'nullable|email|max:255',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                // Validate that any provided guest has at least one field filled
                $guests = $this->input('guests', []);
                if (is_array($guests)) {
                    foreach ($guests as $i => $guest) {
                        if (!empty($guest['first_name']) || !empty($guest['last_name']) || !empty($guest['email'])) {
                            // Has some data — first_name is the minimum required if anything is filled
                            if (empty(trim($guest['first_name'] ?? ''))) {
                                $validator->errors()->add("guests.$i.first_name", 'If adding a guest, at least a first name is required.');
                            }
                        }
                    }
                }
            },
        ];
    }
}
