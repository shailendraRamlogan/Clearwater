<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        private TicketService $ticketService,
    ) {}

    public function downloadPdf(Request $request)
    {
        $ref = $request->query('ref');

        if (!$ref) {
            return response()->json(['error' => 'Booking reference is required'], 400);
        }

        $booking = \App\Models\Booking::where('booking_ref', $ref)->first();

        // Fallback: check if ref is a PrivateTourRequest reference
        if (!$booking) {
            $ptr = \App\Models\PrivateTourRequest::where('booking_ref', $ref)->first();
            if ($ptr && $ptr->booking_id) {
                $booking = \App\Models\Booking::find($ptr->booking_id);
            }
        }

        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        $pdfContent = $this->ticketService->generateTicketPdf($booking);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="tickets-' . $ref . '.pdf"');
    }
}
