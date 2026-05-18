<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount_cents' => $this->amount_cents,
            'status' => $this->status,
            'initiated_by' => $this->whenLoaded('initiator', fn () => [
                'id' => $this->initiator->id,
                'name' => $this->initiator->name,
            ]),
            'confirmed_by' => $this->whenLoaded('confirmer', fn () => $this->confirmer ? [
                'id' => $this->confirmer->id,
                'name' => $this->confirmer->name,
            ] : null),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'transfer_name' => $this->transfer_name,
            'receipt_image' => $this->receipt_image,
            'notes' => $this->notes,
            'rejected_by' => $this->whenLoaded('rejecter', fn () => $this->rejecter ? [
                'id' => $this->rejecter->id,
                'name' => $this->rejecter->name,
            ] : null),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
