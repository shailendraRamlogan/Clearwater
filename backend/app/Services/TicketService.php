<?php

namespace App\Services;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;class TicketService
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

    public function getAllGuestsComplete(Booking $booking): bool
    {
        $booking->loadMissing('guests');
        return $booking->guests->every(function ($guest) {
            return $guest->first_name !== '' && $guest->last_name !== '' && $guest->email !== '';
        });
    }

    public function generateTicketPdf(Booking $booking): string
    {
        $booking->loadMissing(['guests', 'timeSlot.boat']);

        $tickets = [];
        $boatName = $booking->timeSlot?->boat?->name ?? 'N/A';
        $formattedDate = $booking->tour_date->format('F j, Y');
        $startTime = \Carbon\Carbon::parse($booking->timeSlot->start_time)->format('g:i A');

        foreach ($booking->guests as $guest) {
            $qrData = json_encode([
                'booking_ref' => $booking->booking_ref,
                'guest_uuid' => $guest->id,
                'ticket_type' => $guest->is_primary ? 'Primary' : 'Guest',
                'tour_date' => $booking->tour_date->toDateString(),
                'boat_name' => $boatName,
            ]);

            $tickets[] = [
                'guest' => $guest,
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
