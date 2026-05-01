<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\EmailLog;
use App\Models\PrivateTourRequest;
use App\Services\TicketService;
use Resend\Laravel\Facades\Resend;

class EmailService
{
    public function __construct(
        private TicketService $ticketService,
    ) {}

    public function sendConfirmation(Booking $booking): void
    {
        $booking->loadMissing(['primaryGuest', 'timeSlot.boat', 'items', 'addons.addon']);
        $guest = $booking->primaryGuest;

        if (!$guest || !config('services.resend.key')) {
            return;
        }

        $allComplete = $this->ticketService->getAllGuestsComplete($booking);

        try {
            $result = Resend::emails()->send([
                'from' => 'Clear Boat Bahamas <bookings@mail.clearboatbahamas.com>',
                'to' => [$guest->email],
                'subject' => "Booking Confirmed — {$booking->booking_ref}",
                'html' => $this->buildReceiptHtml($booking, $allComplete),
            ]);

            EmailLog::create([
                'booking_id' => $booking->id,
                'recipient' => $guest->email,
                'subject' => "Booking Confirmed — {$booking->booking_ref}",
                'template' => $allComplete ? 'booking_receipt_with_tickets' : 'booking_receipt',
                'resend_id' => $result->id ?? null,
                'status' => 'sent',
            ]);

            // Send separate ticket email if all guests are complete
            if ($allComplete) {
                $this->sendTicketEmail($booking);
            }
        } catch (\Exception $e) {
            EmailLog::create([
                'booking_id' => $booking->id,
                'recipient' => $guest->email,
                'subject' => "Booking Confirmed — {$booking->booking_ref}",
                'template' => 'booking_receipt',
                'status' => 'failed',
            ]);
            throw $e;
        }
    }

    public function sendTicketEmail(Booking $booking): void
    {
        $booking->loadMissing(['primaryGuest', 'timeSlot.boat', 'items']);
        $guest = $booking->primaryGuest;

        if (!$guest || !config('services.resend.key')) {
            return;
        }

        try {
            $downloadUrl = "https://bookings.clearboatbahamas.com/book/confirmation?ref={$booking->booking_ref}&email=" . urlencode($guest->email);

            $result = Resend::emails()->send([
                'from' => 'Clear Boat Bahamas <bookings@mail.clearboatbahamas.com>',
                'to' => [$guest->email],
                'subject' => "Your Tickets: {$booking->booking_ref}",
                'html' => $this->buildTicketHtml($booking, $downloadUrl),
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

    private function buildReceiptHtml(Booking $booking, bool $guestsComplete = false): string
    {
        $guest = $booking->primaryGuest;
        $boat = $booking->timeSlot->boat;
        $date = \Illuminate\Support\Carbon::parse($booking->tour_date)->format('l, F j, Y');
        $time = \Illuminate\Support\Carbon::parse($booking->timeSlot->start_time)->format('g:i A') . ' - ' . \Illuminate\Support\Carbon::parse($booking->timeSlot->end_time)->format('g:i A');
        $subtotal = '$' . number_format($booking->total_price_cents / 100, 2);
        $fees = '$' . number_format(($booking->fees_cents ?? 0) / 100, 2);
        $grandTotal = '$' . number_format(($booking->total_price_cents + ($booking->fees_cents ?? 0)) / 100, 2);

        $confirmationUrl = "https://bookings.clearboatbahamas.com/book/confirmation?ref={$booking->booking_ref}&email=" . urlencode($guest->email);

        // Build items table
        $itemsHtml = '';
        foreach ($booking->items as $item) {
            $label = ucfirst($item->ticket_type) . ($item->ticket_type === 'adult' ? ' Ticket' : ' Ticket');
            $price = '$' . number_format($item->unit_price_cents / 100, 2);
            $lineTotal = '$' . number_format(($item->quantity * $item->unit_price_cents) / 100, 2);
            $itemsHtml .= "<tr>
                <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb;\">{$label}</td>
                <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:center;\">{$item->quantity}</td>
                <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:right;\">{$price}</td>
                <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:right;\">{$lineTotal}</td>
            </tr>";
        }

        // Addons
        foreach ($booking->addons as $addonItem) {
            if ($addonItem->addon) {
                $price = '$' . number_format($addonItem->unit_price_cents / 100, 2);
                $lineTotal = '$' . number_format(($addonItem->quantity * $addonItem->unit_price_cents) / 100, 2);
                $itemsHtml .= "<tr>
                    <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb;\">{$addonItem->addon->title}</td>
                    <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:center;\">{$addonItem->quantity}</td>
                    <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:right;\">{$price}</td>
                    <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:right;\">{$lineTotal}</td>
                </tr>";
            }
        }

        // Conditional CTA section
        if ($guestsComplete) {
            $ctaHtml = <<<CTA
                    <!-- CTA Button -->
                    <tr>
                        <td style="padding:32px; text-align:center;">
                            <a href="{$confirmationUrl}" style="display:inline-block; background-color:#0d9488; color:#ffffff; padding:14px 40px; border-radius:8px; text-decoration:none; font-weight:600; font-size:16px;">
                                View Your Booking Details
                            </a>
                            <p style="margin:12px 0 0; font-size:13px; color:#9ca3af;">
                                Visit this page to view your full booking details, manage your reservation, and download your tickets for check-in.
                            </p>
                        </td>
                    </tr>
CTA;
        } else {
            $ctaHtml = <<<CTA
                    <!-- Pending Guest Info Notice -->
                    <tr>
                        <td style="padding:32px; text-align:center;">
                            <div style="display:inline-block; background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:20px 28px; max-width:460px; text-align:left;">
                                <p style="margin:0 0 8px; font-size:15px; font-weight:600; color:#92400e;">📋 Guest Information Required</p>
                                <p style="margin:0; font-size:14px; color:#78350f; line-height:1.6;">
                                    A staff member will reach out to you shortly to confirm the guest information for your booking. Once all guest details are complete, your tickets will be available for download.
                                </p>
                            </div>
                            <p style="margin:16px 0 0; font-size:13px; color:#9ca3af;">
                                You'll receive a follow-up email with a link to download your tickets once everything is confirmed.
                            </p>
                        </td>
                    </tr>
CTA;
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0; padding:0; background-color:#f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 32px 32px 24px; text-align:center; background: linear-gradient(135deg, #0d9488, #0f766e);">
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700;">Booking Confirmed!</h1>
                            <p style="margin:8px 0 0; color:#ccfbf1; font-size:15px;">Thank you, {$guest->first_name}! Your tour is booked.</p>
                        </td>
                    </tr>

                    <!-- Booking Ref -->
                    <tr>
                        <td style="padding:24px 32px 0; text-align:center;">
                            <span style="display:inline-block; background:#f0fdfa; color:#0d9488; font-size:13px; font-weight:600; padding:6px 16px; border-radius:9999px; letter-spacing:0.5px;">
                                REF: {$booking->booking_ref}
                            </span>
                        </td>
                    </tr>

                    <!-- Trip Details -->
                    <tr>
                        <td style="padding:20px 32px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding-bottom:12px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:10px 14px; background:#f9fafb; border-radius:8px; width:50%;">
                                                    <p style="margin:0; font-size:12px; color:#6b7280;">Date</p>
                                                    <p style="margin:4px 0 0; font-size:15px; font-weight:600; color:#111827;">{$date}</p>
                                                </td>
                                                <td style="width:8px;"></td>
                                                <td style="padding:10px 14px; background:#f9fafb; border-radius:8px; width:50%;">
                                                    <p style="margin:0; font-size:12px; color:#6b7280;">Time</p>
                                                    <p style="margin:4px 0 0; font-size:15px; font-weight:600; color:#111827;">{$time}</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:10px 14px; background:#f9fafb; border-radius:8px; width:50%; margin-top:8px; display:inline-block;">
                                                    <p style="margin:0; font-size:12px; color:#6b7280;">Boat</p>
                                                    <p style="margin:4px 0 0; font-size:15px; font-weight:600; color:#111827;">{$boat->name}</p>
                                                </td>
                                                <td style="width:8px;"></td>
                                                <td style="padding:10px 14px; background:#f9fafb; border-radius:8px; width:50%; margin-top:8px; display:inline-block;">
                                                    <p style="margin:0; font-size:12px; color:#6b7280;">Guests</p>
                                                    <p style="margin:4px 0 0; font-size:15px; font-weight:600; color:#111827;">{$booking->items->sum('quantity')} passengers</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Receipt Breakdown -->
                    <tr>
                        <td style="padding:24px 32px 0;">
                            <h2 style="margin:0 0 12px; font-size:16px; font-weight:700; color:#111827;">Receipt Breakdown</h2>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <thead>
                                    <tr style="background:#f9fafb;">
                                        <th style="padding:10px 12px; text-align:left; font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px;">Item</th>
                                        <th style="padding:10px 12px; text-align:center; font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px;">Qty</th>
                                        <th style="padding:10px 12px; text-align:right; font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px;">Price</th>
                                        <th style="padding:10px 12px; text-align:right; font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$itemsHtml}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" style="padding:10px 12px; text-align:right; font-size:14px; color:#6b7280;">Subtotal</td>
                                        <td style="padding:10px 12px; text-align:right; font-size:14px; color:#111827;">{$subtotal}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" style="padding:4px 12px 10px; text-align:right; font-size:14px; color:#6b7280;">Booking Fee</td>
                                        <td style="padding:4px 12px 10px; text-align:right; font-size:14px; color:#111827;">{$fees}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" style="padding:10px 12px; text-align:right; font-size:18px; font-weight:700; border-top:2px solid #0d9488; color:#0d9488;">Grand Total</td>
                                        <td style="padding:10px 12px; text-align:right; font-size:18px; font-weight:700; border-top:2px solid #0d9488; color:#0d9488;">{$grandTotal}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </td>
                    </tr>

                    {$ctaHtml}

                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 32px; background:#f9fafb; text-align:center;">
                            <p style="margin:0; font-size:13px; color:#6b7280;">
                                Clear Boat Bahamas
                                <br>
                                <span style="font-size:12px; color:#9ca3af;">
                                    Need help? Reply to this email or contact us directly.
                                </span>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function buildTicketHtml(Booking $booking, string $downloadUrl): string
    {
        $guest = $booking->primaryGuest;
        $boat = $booking->timeSlot->boat;
        $date = $booking->tour_date->format('F j, Y');
        $time = \Carbon\Carbon::parse($booking->timeSlot->start_time)->format('g:i A');

        $itemsHtml = '';
        foreach ($booking->items as $item) {
            $label = ucfirst($item->ticket_type) . ' Ticket';
            $price = '$' . number_format($item->unit_price_cents / 100, 2);
            $lineTotal = '$' . number_format(($item->quantity * $item->unit_price_cents) / 100, 2);
            $itemsHtml .= "<tr><td style=\"padding:8px; border:1px solid #eee;\">{$label}</td><td style=\"padding:8px; border:1px solid #eee; text-align:center;\">{$item->quantity}</td><td style=\"padding:8px; border:1px solid #eee; text-align:right;\">{$price}</td><td style=\"padding:8px; border:1px solid #eee; text-align:right;\">{$lineTotal}</td></tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0; padding:0; background-color:#f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="padding: 32px 32px 24px; text-align:center; background: linear-gradient(135deg, #0d9488, #0f766e);">
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700;">Your Tickets Are Ready! 🎫</h1>
                            <p style="margin:8px 0 0; color:#ccfbf1; font-size:15px;">{$booking->booking_ref}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:10px 14px; background:#f9fafb; border-radius:8px; width:50%;">
                                        <p style="margin:0; font-size:12px; color:#6b7280;">{$boat->name}</p>
                                        <p style="margin:4px 0 0; font-size:15px; font-weight:600; color:#111827;">{$date}</p>
                                    </td>
                                    <td style="width:8px;"></td>
                                    <td style="padding:10px 14px; background:#f9fafb; border-radius:8px; width:50%;">
                                        <p style="margin:0; font-size:12px; color:#6b7280;">{$time}</p>
                                        <p style="margin:4px 0 0; font-size:15px; font-weight:600; color:#111827;">{$guest->first_name} {$guest->last_name}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px; text-align:center;">
                            <a href="{$downloadUrl}" style="display:inline-block; background-color:#0d9488; color:#ffffff; padding:14px 40px; border-radius:8px; text-decoration:none; font-weight:600; font-size:16px;">
                                Download Your Tickets (PDF)
                            </a>
                            <p style="margin:12px 0 0; font-size:13px; color:#9ca3af;">
                                Each ticket includes a QR code for check-in. Present them on your phone or printed.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px; background:#f9fafb; text-align:center;">
                            <p style="margin:0; font-size:12px; color:#9ca3af;">Clear Boat Bahamas</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    public function sendGuestsCompletedEmail(Booking $booking): void
    {
        $booking->loadMissing(['primaryGuest', 'timeSlot.boat', 'items', 'addons.addon']);
        $guest = $booking->primaryGuest;

        if (!$guest || !config('services.resend.key')) {
            return;
        }

        $boat = $booking->timeSlot->boat;
        $date = \Illuminate\Support\Carbon::parse($booking->tour_date)->format('l, F j, Y');
        $time = \Illuminate\Support\Carbon::parse($booking->timeSlot->start_time)->format('g:i A') . ' - ' . \Illuminate\Support\Carbon::parse($booking->timeSlot->end_time)->format('g:i A');
        $grandTotal = '$' . number_format(($booking->total_price_cents + ($booking->fees_cents ?? 0)) / 100, 2);
        $confirmationUrl = "https://bookings.clearboatbahamas.com/book/confirmation?ref={$booking->booking_ref}&email=" . urlencode($guest->email);

        $itemsHtml = '';
        foreach ($booking->items as $item) {
            $label = ucfirst($item->ticket_type) . ' Ticket';
            $price = '$' . number_format($item->unit_price_cents / 100, 2);
            $lineTotal = '$' . number_format(($item->quantity * $item->unit_price_cents) / 100, 2);
            $itemsHtml .= "<tr>
                <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb;\">{$label}</td>
                <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:center;\">{$item->quantity}</td>
                <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:right;\">{$price}</td>
                <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:right;\">{$lineTotal}</td>
            </tr>";
        }
        foreach ($booking->addons as $addonItem) {
            if ($addonItem->addon) {
                $price = '$' . number_format($addonItem->unit_price_cents / 100, 2);
                $lineTotal = '$' . number_format(($addonItem->quantity * $addonItem->unit_price_cents) / 100, 2);
                $itemsHtml .= "<tr>
                    <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb;\">{$addonItem->addon->title}</td>
                    <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:center;\">{$addonItem->quantity}</td>
                    <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:right;\">{$price}</td>
                    <td style=\"padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:right;\">{$lineTotal}</td>
                </tr>";
            }
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0; padding:0; background-color:#f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="padding: 32px 32px 24px; text-align:center; background: linear-gradient(135deg, #0d9488, #0f766e);">
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700;">Guest Information Complete! ✅</h1>
                            <p style="margin:8px 0 0; color:#ccfbf1; font-size:15px;">All guest details have been confirmed for your tour.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px 0; text-align:center;">
                            <span style="display:inline-block; background:#f0fdfa; color:#0d9488; font-size:13px; font-weight:600; padding:6px 16px; border-radius:9999px; letter-spacing:0.5px;">
                                REF: {$booking->booking_ref}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:10px 14px; background:#f9fafb; border-radius:8px; width:50%;">
                                        <p style="margin:0; font-size:12px; color:#6b7280;">Date</p>
                                        <p style="margin:4px 0 0; font-size:15px; font-weight:600; color:#111827;">{$date}</p>
                                    </td>
                                    <td style="width:8px;"></td>
                                    <td style="padding:10px 14px; background:#f9fafb; border-radius:8px; width:50%;">
                                        <p style="margin:0; font-size:12px; color:#6b7280;">Time</p>
                                        <p style="margin:4px 0 0; font-size:15px; font-weight:600; color:#111827;">{$time}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;">
                            <p style="margin:0 0 16px; font-size:15px; color:#374151;">Hi {$guest->first_name},</p>
                            <p style="margin:0 0 12px; font-size:15px; color:#374151;">
                                Great news! All guest information for your booking has been completed. Below is your receipt for reference.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
                                <tr style="background:#f9fafb;">
                                    <th style="padding:10px 12px; text-align:left; font-size:13px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Item</th>
                                    <th style="padding:10px 12px; text-align:center; font-size:13px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Qty</th>
                                    <th style="padding:10px 12px; text-align:right; font-size:13px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Price</th>
                                    <th style="padding:10px 12px; text-align:right; font-size:13px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Total</th>
                                </tr>
                                {$itemsHtml}
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px 0; text-align:right;">
                            <p style="margin:0; font-size:16px; font-weight:700; color:#111827;">Total: {$grandTotal}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px; text-align:center;">
                            <p style="margin:0 0 16px; font-size:15px; color:#374151;">
                                Your tickets are ready to download! Click the button below to visit your booking confirmation page, where you will find a <strong>download button</strong> to save your tickets as a PDF.
                            </p>
                            <a href="{$confirmationUrl}" style="display:inline-block; background-color:#0d9488; color:#ffffff; padding:14px 40px; border-radius:8px; text-decoration:none; font-weight:600; font-size:16px;">
                                View Booking & Download Tickets
                            </a>
                            <p style="margin:12px 0 0; font-size:13px; color:#9ca3af;">
                                Each ticket includes a QR code for check-in. Present them on your phone or printed.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px; background:#f9fafb; text-align:center;">
                            <p style="margin:0; font-size:12px; color:#9ca3af;">Clear Boat Bahamas</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        try {
            $result = Resend::emails()->send([
                'from' => 'Clear Boat Bahamas <bookings@mail.clearboatbahamas.com>',
                'to' => [$guest->email],
                'subject' => "Guest Info Complete — Tickets Ready for {$booking->booking_ref}",
                'html' => $html,
            ]);

            EmailLog::create([
                'booking_id' => $booking->id,
                'recipient' => $guest->email,
                'subject' => "Guest Info Complete — Tickets Ready for {$booking->booking_ref}",
                'template' => 'guests_completed_receipt',
                'resend_id' => $result->id ?? null,
                'status' => 'sent',
            ]);
        } catch (\Exception $e) {
            EmailLog::create([
                'booking_id' => $booking->id,
                'recipient' => $guest->email,
                'subject' => "Guest Info Complete — Tickets Ready for {$booking->booking_ref}",
                'template' => 'guests_completed_receipt',
                'status' => 'failed',
            ]);
        }
    }

    // ─── Private Tour Emails ────────────────────────────────────────────────

    public function sendPrivateTourRequestReceived(PrivateTourRequest $request): void
    {
        if (!config('services.resend.key')) {
            return;
        }

        $firstName = $request->contact_first_name;
        $datesHtml = $this->buildPreferredDatesHtml($request);
        $guestCount = $request->totalGuests() . ' guest' . ($request->totalGuests() !== 1 ? 's' : '');
        if ($request->infant_count > 0) {
            $guestCount .= ' + ' . $request->infant_count . ' infant' . ($request->infant_count !== 1 ? 's' : '');
        }

        $occasionHtml = '';
        if ($request->has_occasion && $request->occasion_details) {
            $occasionHtml = "<tr><td style=\"padding:12px 32px;\"><p style=\"margin:0; font-size:14px; color:#374151;\"><strong>Special Occasion:</strong> " . e($request->occasion_details) . "</p></td></tr>";
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0; padding:0; background-color:#f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="padding: 32px 32px 24px; text-align:center; background: linear-gradient(135deg, #0d9488, #0f766e);">
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700;">Private Tour Request Received! ✨</h1>
                            <p style="margin:8px 0 0; color:#ccfbf1; font-size:15px;">Thank you, {$firstName}! We'll be in touch soon.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px 0; text-align:center;">
                            <span style="display:inline-block; background:#f0fdfa; color:#0d9488; font-size:13px; font-weight:600; padding:6px 16px; border-radius:9999px; letter-spacing:0.5px;">
                                REF: {$request->booking_ref}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;">
                            <p style="margin:0 0 16px; font-size:15px; color:#374151; line-height:1.6;">
                                We've received your private tour request! Our team will review your preferred dates and get back to you within <strong>24–48 hours</strong> with a personalized quote.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
                                <tr style="background:#f9fafb;">
                                    <th style="padding:10px 14px; text-align:left; font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px;" colspan="2">Request Summary</th>
                                </tr>
                                <tr><td style="padding:10px 14px; border-bottom:1px solid #f3f4f6; color:#6b7280; font-size:14px;">Guests</td><td style="padding:10px 14px; border-bottom:1px solid #f3f4f6; text-align:right; font-size:14px; font-weight:600;">{$guestCount}</td></tr>
                                {$datesHtml}
                            </table>
                        </td>
                    </tr>
                    {$occasionHtml}
                    <tr>
                        <td style="padding:24px 32px; background:#f9fafb; text-align:center;">
                            <p style="margin:0; font-size:13px; color:#6b7280;">Clear Boat Bahamas<br><span style="font-size:12px; color:#9ca3af;">Need help? Reply to this email or contact us directly.</span></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        $this->sendPrivateTourEmail($request, "Private Tour Request Received — {$request->booking_ref}", $html, 'private_tour_request_received');
    }

    public function sendPrivateTourConfirmed(PrivateTourRequest $request): void
    {
        if (!config('services.resend.key')) {
            return;
        }

        $firstName = $request->contact_first_name;
        $totalPrice = '$' . number_format($request->total_price_cents / 100, 2);
        $fees = '$' . number_format(($request->fees_cents ?? 0) / 100, 2);
        $grandTotal = '$' . number_format($request->grand_total / 100, 2);
        $date = $request->confirmed_tour_date ? \Illuminate\Support\Carbon::parse($request->confirmed_tour_date)->format('l, F j, Y') : 'TBD';
        $time = $request->formatted_time ?? 'TBD';

        $paymentUrl = $request->payment_url;
        $ctaHtml = '';
        if ($paymentUrl) {
            $ctaHtml = "<tr><td style=\"padding:24px 32px; text-align:center;\"><a href=\"{$paymentUrl}\" style=\"display:inline-block; background-color:#0d9488; color:#ffffff; padding:14px 40px; border-radius:8px; text-decoration:none; font-weight:600; font-size:16px;\">Pay Now — {$grandTotal}</a><p style=\"margin:12px 0 0; font-size:13px; color:#9ca3af;\">Click to complete your payment and secure your private tour.</p></td></tr>";
        }

        // Build addon rows for pricing table
        $addonRowsHtml = '';
        $request->loadMissing('addons.addon');
        foreach ($request->addons as $pta) {
            if ($pta->unit_price_cents === null) continue;
            $addonTitle = e($pta->addon->title ?? 'Add-on');
            $addonPrice = '$' . number_format($pta->unit_price_cents / 100, 2);
            $addonRowsHtml .= "<tr><td style=\"padding:10px 14px; border-bottom:1px solid #f3f4f6; font-size:14px; color:#6b7280;\">&nbsp;&nbsp;{$addonTitle}</td><td style=\"padding:10px 14px; border-bottom:1px solid #f3f4f6; text-align:right; font-size:14px; color:#6b7280;\">{$addonPrice}</td></tr>";
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0; padding:0; background-color:#f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="padding: 32px 32px 24px; text-align:center; background: linear-gradient(135deg, #0d9488, #0f766e);">
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700;">Your Private Tour Quote is Ready! 🎉</h1>
                            <p style="margin:8px 0 0; color:#ccfbf1; font-size:15px;">Great news, {$firstName}! Your tour has been confirmed.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px 0; text-align:center;">
                            <span style="display:inline-block; background:#f0fdfa; color:#0d9488; font-size:13px; font-weight:600; padding:6px 16px; border-radius:9999px; letter-spacing:0.5px;">
                                REF: {$request->booking_ref}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:10px 14px; background:#f9fafb; border-radius:8px; width:50%;">
                                        <p style="margin:0; font-size:12px; color:#6b7280;">Confirmed Date</p>
                                        <p style="margin:4px 0 0; font-size:15px; font-weight:600; color:#111827;">{$date}</p>
                                    </td>
                                    <td style="width:8px;"></td>
                                    <td style="padding:10px 14px; background:#f9fafb; border-radius:8px; width:50%;">
                                        <p style="margin:0; font-size:12px; color:#6b7280;">Time</p>
                                        <p style="margin:4px 0 0; font-size:15px; font-weight:600; color:#111827;">{$time}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
                                <tr style="background:#f9fafb;"><th style="padding:10px 14px; text-align:left; font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase;">Pricing</th><th style="padding:10px 14px; text-align:right; font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase;">Amount</th></tr>
                                <tr><td style="padding:10px 14px; border-bottom:1px solid #f3f4f6; font-size:14px;">Tour Price</td><td style="padding:10px 14px; border-bottom:1px solid #f3f4f6; text-align:right; font-size:14px;">{$totalPrice}</td></tr>
                                {$addonRowsHtml}
                                <tr><td style="padding:10px 14px; border-bottom:1px solid #f3f4f6; font-size:14px;">Fees</td><td style="padding:10px 14px; border-bottom:1px solid #f3f4f6; text-align:right; font-size:14px;">{$fees}</td></tr>
                                <tr><td style="padding:10px 14px; font-size:16px; font-weight:700; color:#0d9488;">Total</td><td style="padding:10px 14px; text-align:right; font-size:16px; font-weight:700; color:#0d9488;">{$grandTotal}</td></tr>
                            </table>
                        </td>
                    </tr>
                    {$ctaHtml}
                    <tr>
                        <td style="padding:24px 32px; background:#f9fafb; text-align:center;">
                            <p style="margin:0; font-size:13px; color:#6b7280;">Clear Boat Bahamas<br><span style=\"font-size:12px; color:#9ca3af;\">Need help? Reply to this email or contact us directly.</span></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        $this->sendPrivateTourEmail($request, "Your Private Tour Quote — {$request->booking_ref}", $html, 'private_tour_confirmed');
    }

    public function sendPrivateTourRejected(PrivateTourRequest $request): void
    {
        if (!config('services.resend.key')) {
            return;
        }

        $firstName = $request->contact_first_name;
        $reason = e($request->admin_notes ?? 'Unfortunately, we are unable to accommodate your request at this time.');

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0; padding:0; background-color:#f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="padding: 32px 32px 24px; text-align:center; background: linear-gradient(135deg, #dc2626, #b91c1c);">
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700;">Private Tour Request Update</h1>
                            <p style="margin:8px 0 0; color:#fecaca; font-size:15px;">{$request->booking_ref}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;">
                            <p style="margin:0 0 16px; font-size:15px; color:#374151; line-height:1.6;">
                                Hi {$firstName},
                            </p>
                            <p style="margin:0 0 16px; font-size:15px; color:#374151; line-height:1.6;">
                                Thank you for your interest in a private tour with Clear Boat Bahamas. Unfortunately, we're unable to accommodate your request at this time.
                            </p>
                            <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:16px; margin-bottom:16px;">
                                <p style="margin:0; font-size:14px; color:#991b1b;"><strong>Reason:</strong> {$reason}</p>
                            </div>
                            <p style="margin:0; font-size:15px; color:#374151; line-height:1.6;">
                                We'd love to help you plan an amazing tour — feel free to submit a new request with different dates or group size.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px; background:#f9fafb; text-align:center;">
                            <p style="margin:0; font-size:13px; color:#6b7280;">Clear Boat Bahamas</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        $this->sendPrivateTourEmail($request, "Private Tour Request Update — {$request->booking_ref}", $html, 'private_tour_rejected');
    }

    public function sendPrivateTourPaymentSucceeded(PrivateTourRequest $request): void
    {
        $booking = $request->booking;
        if (!$booking) {
            return;
        }

        // Reuse the standard confirmation email
        $this->sendConfirmation($booking);
    }

    // ─── Private Tour Helpers ────────────────────────────────────────────────

    private function sendPrivateTourEmail(PrivateTourRequest $request, string $subject, string $html, string $template): void
    {
        try {
            $result = Resend::emails()->send([
                'from' => 'Clear Boat Bahamas <bookings@mail.clearboatbahamas.com>',
                'to' => [$request->contact_email],
                'subject' => $subject,
                'html' => $html,
            ]);

            EmailLog::create([
                'booking_id' => $request->booking?->id,
                'recipient' => $request->contact_email,
                'subject' => $subject,
                'template' => $template,
                'resend_id' => $result->id ?? null,
                'status' => 'sent',
            ]);
        } catch (\Exception $e) {
            EmailLog::create([
                'booking_id' => $request->booking?->id,
                'recipient' => $request->contact_email,
                'subject' => $subject,
                'template' => $template,
                'status' => 'failed',
            ]);
        }
    }

    private function buildPreferredDatesHtml(PrivateTourRequest $request): string
    {
        $html = '';
        foreach ($request->preferredDates as $i => $pd) {
            $dateFormatted = \Illuminate\Support\Carbon::parse($pd->date)->format('F j, Y');
            $preference = ucfirst($pd->time_preference);
            $html .= "<tr><td style=\"padding:10px 14px; border-bottom:1px solid #f3f4f6; font-size:14px;\">" . ($i + 1) . ". {$dateFormatted}</td><td style=\"padding:10px 14px; border-bottom:1px solid #f3f4f6; text-align:right; font-size:14px; font-weight:500; color:#0d9488;\">{$preference}</td></tr>";
        }
        return $html;
    }
}
