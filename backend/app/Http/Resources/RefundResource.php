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
            'fees_deducted_cents' => $this->fees_deducted_cents,
            'type' => $this->type,
            'status' => $this->status,
            'reason' => $this->reason,
            'rejection_reason' => $this->rejection_reason,
            'initiated_by' => $this->whenLoaded('initiator', fn () => $this->initiator ? [
                'id' => $this->initiator->id,
                'name' => $this->initiator->name,
            ] : null),
            'approved_by' => $this->whenLoaded('approver', fn () => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ] : null),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
