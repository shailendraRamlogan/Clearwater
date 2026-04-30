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
            'tour_date' => \Illuminate\Support\Carbon::parse($this->tour_date)->format('Y-m-d'),
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
            'subtotal' => ($this->total_price_cents ?? 0) / 100,
            'fees_cents' => $this->fees_cents ?? 0,
            'grand_total' => (($this->total_price_cents ?? 0) + ($this->fees_cents ?? 0)) / 100,
            'fees_breakdown' => $this->whenLoaded('items', function () {
                $subtotalCents = $this->total_price_cents ?? 0;
                $fees = \App\Models\BookingFee::active()->orderBy('sort_order')->get();
                return $fees->map(fn ($fee) => [
                    'name' => $fee->name,
                    'type' => $fee->type,
                    'amount_cents' => $fee->calculateFee($subtotalCents),
                    'display' => $fee->displayValue(),
                ])->values()->all();
            }),
            'status' => $this->status,
            'is_confirmed' => $this->status === 'confirmed',
            'complete_guests_count' => $this->whenLoaded('guests', fn () => 1 + $this->guests->where('is_primary', false)->where('last_name', '!=', '')->where('email', '!=', '')->count()),
            'needs_confirmation' => $this->status === 'pending',
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
