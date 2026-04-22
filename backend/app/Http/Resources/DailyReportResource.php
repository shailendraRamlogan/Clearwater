<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date' => $this['date'],
            'total_bookings' => $this['total_bookings'] ?? 0,
            'total_adults' => $this['total_adults'] ?? 0,
            'total_children' => $this['total_children'] ?? 0,
            'total_revenue' => $this['total_revenue'] ?? 0,
            'bookings' => BookingResource::collection($this['bookings'] ?? []),
        ];
    }
}
