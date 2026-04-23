<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PdfController
{
    public function download(Booking $booking)
    {
        $booking->load(['guests', 'items', 'timeSlot.boat']);

        $pdf = Pdf::loadView('pdf.booking-invoice', [
            'booking' => $booking,
        ])->setPaper('a4', 'portrait');

        $filename = "invoice-{$booking->booking_ref}.pdf";

        return $pdf->download($filename);
    }
}
