<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailabilityRequest;
use App\Http\Resources\TimeSlotResource;
use App\Models\Boat;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class AvailabilityController extends Controller
{
    public function __invoke(AvailabilityRequest $request)
    {
        $date = $request->validated('date');

        $slots = Boat::where('is_active', true)
            ->whereHas('timeSlots', fn($q) => $q->where('is_blocked', false))
            ->with(['timeSlots' => fn($q) => $q->where('is_blocked', false)])
            ->get()
            ->flatMap(fn($boat) => $boat->timeSlots->map(function ($slot) use ($date) {
                $used = Booking::where('time_slot_id', $slot->id)
                    ->where('tour_date', $date)
                    ->where('status', '!=', 'cancelled')
                    ->with('items')
                    ->get()
                    ->sum(fn($b) => $b->items->sum('quantity') + $b->photo_upgrade_count);

                $slot->remaining_capacity = max(0, $slot->max_capacity - $used);
                $slot->boat_name = $slot->boat->name;

                return $slot;
            }))
            ->values();

        return response()->json(['slots' => TimeSlotResource::collection($slots)]);
    }
}
