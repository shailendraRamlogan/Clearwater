<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primaryGuest = $this->whenLoaded('primaryGuest') ?? $this->whenLoaded('guests', fn() => $this->guests->firstWhere('is_primary', true));

        return [
            'id' => $this->booking_ref ?? $this->id,
            'tour_date' => $this->tour_date?->format('Y-m-d'),
            'time_slot' => new TimeSlotResource($this->whenLoaded('timeSlot')),
            'guest' => $primaryGuest ? [
                'first_name' => $primaryGuest->first_name,
                'last_name' => $primaryGuest->last_name,
                'email' => $primaryGuest->email,
                'phone' => $primaryGuest->phone,
            ] : null,
            'items' => BookingItemResource::collection($this->whenLoaded('items')),
            'package_upgrade' => $this->photo_upgrade_count > 0,
            'special_occasion' => !empty($this->special_occasion),
            'special_comment' => $this->special_comment ?? '',
            'total_price' => ($this->total_price_cents ?? 0) / 100,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
