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
            'regular_bookings' => $this['regular_bookings'] ?? 0,
            'private_bookings' => $this['private_bookings'] ?? 0,
            'total_adults' => $this['total_adults'] ?? 0,
            'total_children' => $this['total_children'] ?? 0,
            'total_revenue' => $this['total_revenue'] ?? 0,
            'regular_revenue' => $this['regular_revenue'] ?? 0,
            'private_revenue' => $this['private_revenue'] ?? 0,
            'bookings' => BookingResource::collection($this['bookings'] ?? []),
        ];
    }
}
