<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\EmailLog;
use App\Services\TicketService;
use Resend\Laravel\Facades\Resend;

class EmailService
{
    public function __construct(
        private TicketService $ticketService,
    ) {}

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

            // Send ticket email if all guests are complete
            if ($this->ticketService->getAllGuestsComplete($booking)) {
                $this->sendTicketEmail($booking);
            }
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

    public function sendTicketEmail(Booking $booking): void
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
                'subject' => "Your Tickets: {$booking->booking_ref}",
                'html' => $this->buildTicketHtml($booking),
            ]);

            EmailLog::create([
                'booking_id' => $booking->id,
                'recipient' => $guest->email,
                'subject' => "Your Tickets: {$booking->booking_ref}",
                'template' => 'ticket_download',
                'resend_id' => $result->id ?? null,
                'status' => 'sent',
            ]);
        } catch (\Exception $e) {
            EmailLog::create([
                'booking_id' => $booking->id,
                'recipient' => $guest->email,
                'subject' => "Your Tickets: {$booking->booking_ref}",
                'template' => 'ticket_download',
                'status' => 'failed',
            ]);
        }
    }

    private function buildTicketHtml(Booking $booking): string
    {
        $guest = $booking->primaryGuest;
        $boat = $booking->timeSlot->boat;
        $date = $booking->tour_date->format('l, F j, Y');
        $time = $booking->timeSlot->start_time->format('g:i A') . ' - ' . $booking->timeSlot->end_time->format('g:i A');
        $total = '$' . number_format($booking->grand_total / 100, 2);
        $downloadUrl = 'https://clearwater.ourea.tech/api/tickets/pdf?ref=' . $booking->booking_ref;

        $itemsHtml = '';
        foreach ($booking->items as $item) {
            $price = '$' . number_format($item->unit_price_cents / 100, 2);
            $lineTotal = '$' . number_format(($item->quantity * $item->unit_price_cents) / 100, 2);
            $itemsHtml .= "<tr><td style=\"padding:8px; border:1px solid #eee;\">{$item->ticket_type}</td><td style=\"padding:8px; border:1px solid #eee; text-align:center;\">{$item->quantity}</td><td style=\"padding:8px; border:1px solid #eee; text-align:right;\">{$price}</td><td style=\"padding:8px; border:1px solid #eee; text-align:right;\">{$lineTotal}</td></tr>";
        }

        return <<<HTML
        <div style="font-family: system-ui, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2>Your Tickets Are Ready! 🎫</h2>
            <p>Hi {$guest->first_name},</p>
            <p>Your booking with <strong>Clear Boat Bahamas</strong> is confirmed! Download your tickets below.</p>

            <table style="width:100%; border-collapse:collapse; margin: 20px 0;">
                <tr><td style="padding:8px; border:1px solid #eee;"><strong>Reference</strong></td><td style="padding:8px; border:1px solid #eee;">{$booking->booking_ref}</td></tr>
                <tr><td style="padding:8px; border:1px solid #eee;"><strong>Boat</strong></td><td style="padding:8px; border:1px solid #eee;">{$boat->name}</td></tr>
                <tr><td style="padding:8px; border:1px solid #eee;"><strong>Date</strong></td><td style="padding:8px; border:1px solid #eee;">{$date}</td></tr>
                <tr><td style="padding:8px; border:1px solid #eee;"><strong>Time</strong></td><td style="padding:8px; border:1px solid #eee;">{$time}</td></tr>
            </table>

            <table style="width:100%; border-collapse:collapse; margin: 20px 0;">
                <tr style="background:#f8f8f8;"><th style="padding:8px; border:1px solid #eee; text-align:left;">Ticket</th><th style="padding:8px; border:1px solid #eee;">Qty</th><th style="padding:8px; border:1px solid #eee; text-align:right;">Price</th><th style="padding:8px; border:1px solid #eee; text-align:right;">Total</th></tr>
                {$itemsHtml}
            </table>

            <p style="text-align:right; font-size:18px; font-weight:bold;">Total: {$total}</p>

            <div style="text-align:center; margin:30px 0;">
                <a href="{$downloadUrl}" style="display:inline-block; background-color:#0d9488; color:white; padding:14px 32px; border-radius:8px; text-decoration:none; font-weight:600; font-size:16px;">Download Your Tickets</a>
            </div>

            <p style="color:#666; font-size:13px;">Present your QR codes at check-in. See you on board!</p>
            <p><em>Clear Boat Bahamas</em></p>
        </div>
        HTML;
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
