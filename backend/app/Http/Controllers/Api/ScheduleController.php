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
}
