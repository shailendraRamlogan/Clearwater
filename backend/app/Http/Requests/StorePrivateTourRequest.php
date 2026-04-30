<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrivateTourRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'contact_first_name' => 'required|string|max:100',
            'contact_last_name' => 'required|string|max:100',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:30',
            'adult_count' => 'required|integer|min:0|max:10',
            'child_count' => 'required|integer|min:0|max:10',
            'infant_count' => 'required|integer|min:0|max:10',
            'has_occasion' => 'boolean',
            'occasion_details' => 'nullable|string|max:500',
            'preferred_dates' => 'required|array|min:1|max:5',
            'preferred_dates.*.date' => 'required|date|date_format:Y-m-d',
            'preferred_dates.*.time_preference' => 'required|in:morning,afternoon',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $adults = (int) $this->input('adult_count', 0);
                $children = (int) $this->input('child_count', 0);

                if ($adults + $children > 10) {
                    $validator->errors()->add('adult_count', 'Total guests (adults + children) cannot exceed 10.');
                }

                if ($adults + $children === 0) {
                    $validator->errors()->add('adult_count', 'At least 1 adult or child is required.');
                }

                if ($this->boolean('has_occasion') && empty(trim($this->input('occasion_details', '')))) {
                    $validator->errors()->add('occasion_details', 'Please describe the occasion.');
                }

                // Check for duplicate dates
                $dates = array_map(fn($d) => $d['date'] ?? '', $this->input('preferred_dates', []));
                if (count($dates) !== count(array_unique($dates))) {
                    $validator->errors()->add('preferred_dates', 'Each preferred date must be unique.');
                }
            },
        ];
    }
}
