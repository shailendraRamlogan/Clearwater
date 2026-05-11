<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyReportRequest;
use App\Http\Resources\DailyReportResource;
use App\Models\Booking;

class ReportController extends Controller
{
    public function daily(DailyReportRequest $request)
    {
        $date = $request->validated('date');

        $bookings = Booking::with(['timeSlot.boat', 'primaryGuest', 'items'])
            ->where('tour_date', $date)
            ->orderBy('created_at')
            ->get();

        $regularBookings = $bookings->where('source_type', '!=', 'private');
        $privateBookings = $bookings->where('source_type', 'private');

        $report = [
            'date' => $date,
            'total_bookings' => $bookings->count(),
            'regular_bookings' => $regularBookings->count(),
            'private_bookings' => $privateBookings->count(),
            'total_adults' => $bookings->sum(fn($b) => $b->items->where('ticket_type', 'adult')->sum('quantity')),
            'total_children' => $bookings->sum(fn($b) => $b->items->where('ticket_type', 'child')->sum('quantity')),
            'total_revenue' => $bookings->sum(fn($b) => $b->total_price_cents) / 100,
            'regular_revenue' => $regularBookings->sum(fn($b) => $b->total_price_cents) / 100,
            'private_revenue' => $privateBookings->sum(fn($b) => $b->total_price_cents) / 100,
            'bookings' => $bookings,
        ];

        return response()->json(['report' => new DailyReportResource($report)]);
    }

    public function schedulePdf(DailyReportRequest $request)
    {
        $date = $request->validated('date');
        $bookings = Booking::with(['timeSlot.boat', 'primaryGuest', 'items'])
            ->where('tour_date', $date)
            ->orderBy('time_slot_id')
            ->get();

        $html = view('pdf.schedule', ['date' => $date, 'bookings' => $bookings])->render();

        return response()->streamDownload(function () use ($html) {
            echo app('dompdf')->loadHtml($html)->output();
        }, "schedule-{$date}.pdf");
    }
}
