<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\EmailLog;
use Resend\Laravel\Facades\Resend;

class EmailService
{
    public function sendConfirmation(Booking $booking): void
    {
        $booking->loadMissing(['primaryGuest', 'timeSlot.boat', 'items']);
        $guest = $booking->primaryGuest;

        if (!$guest || !config('services.resend.api_key')) {
            return;
        }

        try {
            $result = Resend::emails()->send([
                'from' => 'Clear Boat Bahamas <bookings@clearboatbahamas.com>',
                'to' => [$guest->email],
                'subject' => "Booking Received: {$booking->booking_ref}",
                'html' => $this->buildHtml($booking),
            ]);

            EmailLog::create([
                'booking_id' => $booking->id,
                'recipient' => $guest->email,
                'subject' => "Booking Received: {$booking->booking_ref}",
                'template' => 'booking_confirmation',
                'resend_id' => $result->id ?? null,
                'status' => 'sent',
            ]);
        } catch (\Exception $e) {
            EmailLog::create([
                'booking_id' => $booking->id,
                'recipient' => $guest->email,
                'subject' => "Booking Received: {$booking->booking_ref}",
                'template' => 'booking_confirmation',
                'status' => 'failed',
            ]);
            throw $e;
        }
    }

    private function buildHtml(Booking $booking): string
    {
        $guest = $booking->primaryGuest;
        $boat = $booking->timeSlot->boat;
        $date = $booking->tour_date->format('l, F j, Y');
        $time = $booking->timeSlot->start_time->format('g:i A') . ' - ' . $booking->timeSlot->end_time->format('g:i A');
        $total = '$' . number_format($booking->total_price_cents / 100, 2);

        return <<<HTML
        <div style="font-family: system-ui, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2>Booking Received 🎉</h2>
            <p>Hi {$guest->first_name},</p>
            <p>We've received your booking with <strong>Clear Boat Bahamas</strong>. Please complete payment to confirm your reservation.</p>

            <table style="width:100%; border-collapse:collapse; margin: 20px 0;">
                <tr><td style="padding:8px; border:1px solid #eee;"><strong>Reference</strong></td><td style="padding:8px; border:1px solid #eee;">{$booking->booking_ref}</td></tr>
                <tr><td style="padding:8px; border:1px solid #eee;"><strong>Boat</strong></td><td style="padding:8px; border:1px solid #eee;">{$boat->name}</td></tr>
                <tr><td style="padding:8px; border:1px solid #eee;"><strong>Date</strong></td><td style="padding:8px; border:1px solid #eee;">{$date}</td></tr>
                <tr><td style="padding:8px; border:1px solid #eee;"><strong>Time</strong></td><td style="padding:8px; border:1px solid #eee;">{$time}</td></tr>
                <tr><td style="padding:8px; border:1px solid #eee;"><strong>Total</strong></td><td style="padding:8px; border:1px solid #eee;">{$total}</td></tr>
            </table>

            <p>We look forward to seeing you on board!</p>
            <p><em>Clear Boat Bahamas</em></p>
        </div>
        HTML;
    }
}
