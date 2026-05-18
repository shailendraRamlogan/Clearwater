<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_ref' => $this->booking_ref,
            'tour_date' => $this->tour_date?->format('Y-m-d'),
            'time_slot' => $this->whenLoaded('timeSlot', fn () => [
                'id' => $this->timeSlot->id,
                'start_time' => $this->timeSlot->start_time,
                'end_time' => $this->timeSlot->end_time,
            ]),
            'status' => $this->status,
            'source_type' => $this->source_type,
            'special_occasion' => $this->special_occasion,
            'special_comment' => $this->special_comment,
            'total_price_cents' => $this->total_price_cents,
            'fees_cents' => $this->fees_cents,
            'grand_total_cents' => ($this->total_price_cents ?? 0) + ($this->fees_cents ?? 0),
            'guests' => $this->whenLoaded('guests', fn () => $this->guests->map(fn ($g) => [
                'first_name' => $g->first_name,
                'last_name' => $g->last_name,
                'email' => $g->email,
                'phone' => $g->phone,
                'is_primary' => $g->is_primary,
            ])),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'ticket_type' => $i->ticket_type,
                'quantity' => $i->quantity,
                'unit_price_cents' => $i->unit_price_cents,
            ])),
            'addons' => $this->whenLoaded('addons', fn () => $this->addons->map(fn ($a) => [
                'addon_id' => $a->addon_id,
                'quantity' => $a->quantity,
                'unit_price_cents' => $a->unit_price_cents,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($p) => [
                'id' => $p->id,
                'amount_cents' => $p->amount_cents,
                'status' => $p->status,
                'created_at' => $p->created_at?->toIso8601String(),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
