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

        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        if (!$this->ticketService->getAllGuestsComplete($booking)) {
            return response()->json(['error' => 'All guest information must be completed before downloading tickets'], 422);
        }

        $pdfContent = $this->ticketService->generateTicketPdf($booking);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="tickets-' . $ref . '.pdf"');
    }

    public function preview(Request $request)
    {
        $ref = $request->query('ref');
        $email = $request->query('email');

        if (!$ref) {
            return response()->json(['error' => 'Booking reference is required'], 400);
        }

        $booking = \App\Models\Booking::where('booking_ref', $ref)
            ->with(['guests', 'timeSlot.boat'])
            ->first();

        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        $boatName = $booking->timeSlot?->boat?->name ?? 'N/A';

        $tickets = $booking->guests->map(function ($guest) use ($booking, $boatName) {
            $qrData = json_encode([
                'booking_ref' => $booking->booking_ref,
                'guest_uuid' => $guest->id,
                'ticket_type' => $guest->is_primary ? 'Primary' : 'Guest',
                'tour_date' => $booking->tour_date->toDateString(),
                'boat_name' => $boatName,
            ]);

            return [
                'guest_name' => $guest->full_name,
                'ticket_type' => $guest->is_primary ? 'Primary' : 'Guest',
                'qr_svg' => $this->ticketService->generateQrSvg($qrData),
            ];
        });

        return response()->json([
            'booking_ref' => $booking->booking_ref,
            'all_guests_complete' => $this->ticketService->getAllGuestsComplete($booking),
            'tickets' => $tickets,
        ]);
    }
}
