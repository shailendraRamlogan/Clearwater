<?php

namespace App\Http\Controllers;

use App\Models\BookingGuest;
use App\Models\TimeSlot;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ManifestExportController
{
    public function download(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'boat_id' => 'nullable|string',
            'time_slot_id' => 'nullable|string',
            'format' => 'required|in:csv,pdf',
        ]);

        $date = $request->input('date');
        $boatId = $request->input('boat_id');
        $timeSlotId = $request->input('time_slot_id');
        $format = $request->input('format');

        $guests = BookingGuest::query()
            ->whereHas('booking', function ($q) use ($date, $timeSlotId, $boatId) {
                $q->where('status', 'confirmed');
                $q->where('tour_date', $date);
                if ($timeSlotId) {
                    $q->where('time_slot_id', $timeSlotId);
                } elseif ($boatId) {
                    $q->whereRaw('EXISTS (SELECT 1 FROM time_slots WHERE time_slots.id = bookings.time_slot_id AND time_slots.boat_id = ?)', [$boatId]);
                }
            })
            ->with(['booking.items'])
            ->get();

        \Log::info('Manifest export query', [
            'date' => $date,
            'boat_id' => $boatId,
            'time_slot_id' => $timeSlotId,
            'guests_count' => $guests->count(),
            'guests' => $guests->map(fn($g) => $g->first_name . ' ' . $g->last_name)->toArray(),
        ]);

        $boatName = null;
        $slotLabel = null;

        if ($timeSlotId) {
            $slot = TimeSlot::find($timeSlotId);
            $boatName = $slot?->boat?->name;
            $slotLabel = $slot?->start_label . ' — ' . $slot?->end_label;
        } elseif ($boatId) {
            $slot = TimeSlot::where('boat_id', $boatId)->first();
            $boatName = $slot?->boat?->name;
        }

        $meta = [
            'date' => Carbon::parse($date)->format('F j, Y'),
            'boat' => $boatName ?? 'All Vessels',
            'slot' => $slotLabel ?? 'All Slots',
        ];

        if ($format === 'csv') {
            $csv = "Passenger Manifest — Clear Boat Bahamas\n";
            $csv .= "Date: {$meta['date']}\n";
            $csv .= "Vessel: {$meta['boat']}\n";
            $csv .= "Time Slot: {$meta['slot']}\n";
            $csv .= "Total Passengers: {$guests->count()}\n\n";
            $csv .= "Booking Ref,Guest Name,Email,Phone,Tickets,Primary\n";

            foreach ($guests as $g) {
                $csv .= sprintf(
                    "%s,\"%s\",%s,%s,%s,%s\n",
                    $g->booking->booking_ref,
                    $g->first_name . ' ' . $g->last_name,
                    $g->email ?? '',
                    $g->phone ?? '',
                    $g->booking->items->sum('quantity'),
                    $g->is_primary ? 'Yes' : 'No'
                );
            }

            return response()->json([
                'content' => base64_encode($csv),
                'filename' => "manifest-{$date}.csv",
                'mime' => 'text/csv',
            ]);
        }

        $html = view('pdf.passenger-manifest', array_merge($meta, [
            'guests' => $guests,
            'total' => $guests->count(),
        ]))->render();

        $pdf = Pdf::loadHtml($html)->output();

        return response()->json([
            'content' => base64_encode($pdf),
            'filename' => "manifest-{$date}.pdf",
            'mime' => 'application/pdf',
            'guests_count' => $guests->count(),
        ]);
    }
}
