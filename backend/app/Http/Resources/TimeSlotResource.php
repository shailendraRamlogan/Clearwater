<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_time' => $this->start_time->format('H:i'),
            'end_time' => $this->end_time->format('H:i'),
            'boat_id' => $this->boat_id,
            'boat_name' => $this->whenLoaded('boat', fn() => $this->boat->name),
            'remaining_capacity' => $this->when(isset($this->remaining_capacity), $this->remaining_capacity),
            'max_capacity' => $this->max_capacity,
            'is_blocked' => $this->is_blocked,
        ];
    }
}
