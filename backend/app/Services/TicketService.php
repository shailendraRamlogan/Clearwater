<?php

namespace App\Services;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class TicketService
{
    public function generateQrBase64(string $data): string
    {
        $options = new QROptions;
        $options->outputInterface = \chillerlan\QRCode\Output\QRGdImagePNG::class;
        $options->scale = 5;
        $options->valuesUseGdTrueColor = true;

        $result = (new QRCode($options))->render($data);

        // v6 returns data URI
        if (str_starts_with($result, 'data:image/png;base64,')) {
            return $result;
        }

        return 'data:image/png;base64,' . base64_encode($result);
    }

    public function generateTicketPdf(Booking $booking): string
    {
        $booking->loadMissing(['guests', 'items', 'timeSlot.boat']);

        // For private tours, load the linked PTR for custom time/boat info
        $ptr = null;
        if ($booking->source_type === 'private') {
            $ptr = \App\Models\PrivateTourRequest::where('booking_id', $booking->id)->first();
        }

        $boatName = $booking->timeSlot?->boat?->name ?? 'Private Tour';
        $formattedDate = $booking->tour_date->format('F j, Y');

        // Use PTR times for private tours, time slot times for regular bookings
        if ($ptr && $ptr->confirmed_start_time) {
            $startTime = \Carbon\Carbon::parse($ptr->confirmed_start_time)->format('g:i A');
        } else {
            $startTime = $booking->timeSlot
                ? \Carbon\Carbon::parse($booking->timeSlot->start_time)->format('g:i A')
                : 'N/A';
        }

        $primaryGuest = $booking->guests->firstWhere('is_primary', true) ?? $booking->guests->first();

        $tickets = [];
        $ticketNum = 0;

        foreach ($booking->items as $item) {
            $ticketType = $item->ticket_type ?? ($item->type ?? 'General');
            $quantity = $item->quantity ?? 1;

            for ($i = 0; $i < $quantity; $i++) {
                $ticketNum++;

                $qrData = json_encode([
                    'booking_ref' => $booking->booking_ref,
                    'ticket_num' => $ticketNum,
                    'ticket_type' => ucfirst($ticketType),
                    'tour_date' => $booking->tour_date->toDateString(),
                    'boat_name' => $boatName,
                ]);

                $tickets[] = [
                    'guest_name' => ($ticketNum === 1 && $primaryGuest)
                        ? $primaryGuest->first_name . ' ' . $primaryGuest->last_name
                        : 'Guest ' . ($ticketNum - 1),
                    'is_primary' => $ticketNum === 1,
                    'ticket_type' => ucfirst($ticketType),
                    'qr_img' => $this->generateQrBase64($qrData),
                    'boat_name' => $boatName,
                    'formatted_date' => $formattedDate,
                    'start_time' => $startTime,
                    'booking_ref' => $booking->booking_ref,
                ];
            }
        }

        // Fallback: if no items, generate one ticket for the primary guest
        if (empty($tickets) && $primaryGuest) {
            $qrData = json_encode([
                'booking_ref' => $booking->booking_ref,
                'ticket_num' => 1,
                'ticket_type' => 'General',
                'tour_date' => $booking->tour_date->toDateString(),
                'boat_name' => $boatName,
            ]);

            $tickets[] = [
                'guest_name' => $primaryGuest->first_name . ' ' . $primaryGuest->last_name,
                'is_primary' => true,
                'ticket_type' => 'General',
                'qr_img' => $this->generateQrBase64($qrData),
                'boat_name' => $boatName,
                'formatted_date' => $formattedDate,
                'start_time' => $startTime,
                'booking_ref' => $booking->booking_ref,
            ];
        }

        $pdf = Pdf::loadView('pdf.ticket', [
            'tickets' => $tickets,
            'booking' => $booking,
        ]);

        return $pdf->output();
    }
}
