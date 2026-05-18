<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'booking_ref' => $this->whenLoaded('booking', fn () => $this->booking->booking_ref),
            'payment_id' => $this->payment_id,
            'stripe_refund_id' => $this->stripe_refund_id,
            'amount_cents' => $this->amount_cents,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'status' => $this->status,
            'initiated_by' => $this->initiated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
