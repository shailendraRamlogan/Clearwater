<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ticket_type' => $this->ticket_type,
            'quantity' => $this->quantity,
            'unit_price' => ($this->unit_price_cents ?? 0) / 100,
        ];
    }
}
