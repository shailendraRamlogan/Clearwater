<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\BookingItem;
use App\Models\BookingAddon;
use App\Models\Payment;
use App\Models\TimeSlot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;

class RebookService
{
    public function __construct(
        private EmailService $emailService,
    ) {}

    /**
     * Rebook a booking to a new date.
     *
     * @return array{booking: Booking, payment_link: ?string, warnings: string[]}
     * @throws \Exception
     */
    public function rebook(Booking $original, Carbon $newDate, int $feeCents, User $admin): array
    {
        $warnings = [];

        // Pre-transaction validations
        if (!in_array($original->status, ['confirmed', 'pending'])) {
            throw new \InvalidArgumentException("Cannot rebook a {$original->status} booking.");
        }

        if ($newDate->eq($original->tour_date)) {
            throw new \InvalidArgumentException('New date must differ from the current tour date.');
        }

        if ($newDate->isToday() || $newDate->isPast()) {
            throw new \InvalidArgumentException('New date must be in the future.');
        }

        if ($feeCents < 0) {
            throw new \InvalidArgumentException('Rebooking fee cannot be negative.');
        }

        // Check customer email exists if fee > 0
        $original->loadMissing('primaryGuest');
        $primaryGuest = $original->primaryGuest;
        if ($feeCents > 0 && (!$primaryGuest || empty($primaryGuest->email))) {
            throw new \InvalidArgumentException('Cannot apply a rebooking fee — no customer email on file. Either set the fee to $0 or add a customer email first.');
        }

        // Capacity check (skip for private tours without time slots)
        if ($original->time_slot_id) {
            $timeSlot = TimeSlot::where('id', $original->time_slot_id)->first();
            if ($timeSlot) {
                $ticketCount = $original->items->sum('quantity');
                $existingGuests = Booking::where('time_slot_id', $original->time_slot_id)
                    ->where('tour_date', $newDate)
                    ->whereNotIn('status', ['cancelled'])
                    ->get()
                    ->sum(fn ($b) => $b->items->sum('quantity'));

                if ($existingGuests + $ticketCount > $timeSlot->max_capacity) {
                    throw new \InvalidArgumentException(
                        "The selected time slot does not have enough capacity on {$newDate->format('M j, Y')}. " .
                        "Currently {$existingGuests} booked, need {$ticketCount} more, max is {$timeSlot->max_capacity}."
                    );
                }
            }
        }

        // Load relationships for cloning
        $original->loadMissing(['guests', 'items', 'addons']);

        // ── Transaction ──
        $newBooking = DB::transaction(function () use ($original, $newDate, $feeCents, $admin) {
            // Lock original
            $original = Booking::lockForUpdate()->find($original->id);

            // Re-validate status after lock
            if (!in_array($original->status, ['confirmed', 'pending'])) {
                throw new \RuntimeException("Booking status changed to {$original->status} during rebook. Please refresh and try again.");
            }

            // Cancel original
            $original->update(['status' => Booking::STATUS_CANCELLED]);

            // Clone booking
            $newBooking = Booking::create([
                'tour_date' => $newDate,
                'time_slot_id' => $original->time_slot_id,
                'status' => Booking::STATUS_CONFIRMED,
                'source_type' => $original->source_type ?? 'regular',
                'photo_upgrade_count' => $original->photo_upgrade_count,
                'special_occasion' => $original->special_occasion,
                'special_comment' => $original->special_comment,
                'total_price_cents' => $original->total_price_cents,
                'fees_cents' => $original->fees_cents,
                'total_guests' => $original->total_guests,
                'is_confirmed' => true,
                'needs_confirmation' => false,
                'booking_agent_id' => $original->booking_agent_id,
                'commission_cents' => $original->commission_cents,
                'commission_percent' => $original->commission_percent,
                'sales_rep_name' => $original->sales_rep_name,
                'rebooked_from_booking_id' => $original->id,
                'rebooked_at' => now(),
                'rebooked_by' => $admin->id,
                'rebook_fee_cents' => $feeCents,
            ]);

            // Clone guests
            foreach ($original->guests as $guest) {
                BookingGuest::create([
                    'booking_id' => $newBooking->id,
                    'first_name' => $guest->first_name,
                    'last_name' => $guest->last_name,
                    'email' => $guest->email,
                    'phone' => $guest->phone,
                    'is_primary' => $guest->is_primary,
                ]);
            }

            // Clone items
            foreach ($original->items as $item) {
                BookingItem::create([
                    'booking_id' => $newBooking->id,
                    'ticket_type' => $item->ticket_type,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $item->unit_price_cents,
                ]);
            }

            // Clone addons
            foreach ($original->addons as $addon) {
                BookingAddon::create([
                    'booking_id' => $newBooking->id,
                    'addon_id' => $addon->addon_id,
                    'quantity' => $addon->quantity,
                    'unit_price_cents' => $addon->unit_price_cents,
                ]);
            }

            return $newBooking;
        });

        // ── Post-commit: emails + payment ──
        $paymentLink = null;
        $newBooking->loadMissing('primaryGuest', 'originalBooking');

        // Always send confirmation
        try {
            $this->emailService->sendConfirmation($newBooking);
        } catch (\Exception $e) {
            Log::warning('Rebook confirmation email failed: ' . $e->getMessage());
            $warnings[] = 'Confirmation email could not be sent.';
        }

        // If fee > 0, create Stripe Checkout Session and send fee email
        if ($feeCents > 0) {
            $stripeKey = config('services.stripe.secret');
            if ($stripeKey) {
                try {
                    Stripe::setApiKey($stripeKey);

                    $customerEmail = $newBooking->primaryGuest?->email;
                    $originalRef = $original->booking_ref;

                    $session = \Stripe\Checkout\Session::create([
                        'payment_intent_data' => [
                            'metadata' => [
                                'booking_ref' => $newBooking->booking_ref,
                                'type' => 'rebook_fee',
                                'original_booking_ref' => $originalRef,
                            ],
                        ],
                        'mode' => 'payment',
                        'line_items' => [[
                            'price_data' => [
                                'currency' => 'usd',
                                'product_data' => [
                                    'name' => "Rescheduling Fee - {$newBooking->booking_ref}",
                                    'description' => "Rescheduling from {$original->tour_date->format('M j, Y')} to {$newDate->format('M j, Y')}",
                                ],
                                'unit_amount' => $feeCents,
                            ],
                            'quantity' => 1,
                        ]],
                        'customer_email' => $customerEmail,
                        'success_url' => "https://bookings.clearboatbahamas.com/book/confirmation?ref={$newBooking->booking_ref}&email=" . urlencode($customerEmail),
                        'cancel_url' => "https://bookings.clearboatbahamas.com/book/confirmation?ref={$newBooking->booking_ref}&email=" . urlencode($customerEmail),
                        'expires_at' => now()->addDays(7)->timestamp,
                    ]);

                    $paymentLink = $session->url;

                    Payment::create([
                        'booking_id' => $newBooking->id,
                        'stripe_intent_id' => $session->payment_intent ?? "cs_{$session->id}",
                        'amount_cents' => $feeCents,
                        'status' => 'pending',
                        'metadata' => [
                            'type' => 'rebook_fee',
                            'checkout_session_id' => $session->id,
                        ],
                    ]);

                    // Send fee email
                    $this->emailService->sendRebookFeeRequest($newBooking, $paymentLink);

                } catch (\Exception $e) {
                    Log::error('Stripe rebook fee setup failed: ' . $e->getMessage());
                    $warnings[] = 'Rebook succeeded but the fee payment link could not be generated. Contact the customer manually.';
                }
            } else {
                $warnings[] = 'Stripe is not configured. Fee of $' . number_format($feeCents / 100, 2) . ' must be collected manually.';
            }
        }

        return [
            'booking' => $newBooking,
            'payment_link' => $paymentLink,
            'warnings' => $warnings,
        ];
    }
}
