<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlockScheduleRequest;
use App\Models\TimeSlot;

class ScheduleController extends Controller
{
    public function block(BlockScheduleRequest $request)
    {
        $validated = $request->validated();

        $query = TimeSlot::query();
        if (!empty($validated['time_slot_id'])) {
            $query->where('id', $validated['time_slot_id']);
        }
        $query->update(['is_blocked' => true]);

        return response()->json(['message' => 'Schedule blocked']);
    }

    public function unblock(BlockScheduleRequest $request)
    {
        $validated = $request->validated();

        $query = TimeSlot::query();
        if (!empty($validated['time_slot_id'])) {
            $query->where('id', $validated['time_slot_id']);
        }
        $query->update(['is_blocked' => false]);

        return response()->json(['message' => 'Schedule unblocked']);
    }

    public function blocked()
    {
        $month = request()->query('month');
        if ($month) {
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));
        } else {
            $startDate = now()->startOfMonth()->format('Y-m-d');
            $endDate = now()->endOfMonth()->format('Y-m-d');
        }

        $blocked = TimeSlot::where('is_blocked', true)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereNull('effective_from')
                    ->orWhereBetween('effective_from', [$startDate, $endDate]);
            })
            ->get(['id', 'day', 'start_time', 'end_time', 'is_blocked', 'effective_from', 'effective_until'])
            ->map(fn($slot) => [
                'date' => $slot->effective_from?->format('Y-m-d'),
                'time_slot_id' => $slot->id,
                'day' => $slot->day,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'reason' => 'Manually blocked',
            ]);

        return response()->json(['blocked' => $blocked]);
    }
}
