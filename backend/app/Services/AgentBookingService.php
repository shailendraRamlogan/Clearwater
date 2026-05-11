<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\BookingGuest;
use App\Models\BookingItem;
use App\Models\PrivateTourAddon;
use App\Models\PrivateTourRequest;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class AgentBookingService
{
    public function __construct(
        private FeeService $feeService,
        private EmailService $emailService,
    ) {}

    /**
     * Create a regular sailing booking on behalf of a guest.
     */
    public function createRegularBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            // Capacity check
            $timeSlot = TimeSlot::where('id', $data['time_slot_id'])->lockForUpdate()->first();
            if (!$timeSlot) {
                throw new \Exception('Time slot not found.');
            }

            $totalGuests = ($data['adult_count'] ?? 0) + ($data['child_count'] ?? 0);
            if ($totalGuests === 0) {
                throw new \Exception('At least one guest is required.');
            }

            $existingBooked = Booking::where('time_slot_id', $data['time_slot_id'])
                ->where('tour_date', $data['tour_date'])
                ->whereNotIn('status', ['cancelled'])
                ->where('source_type', '!=', 'private')
                ->get()
                ->sum(fn ($b) => $b->items->sum('quantity'));

            if ($existingBooked + $totalGuests > $timeSlot->max_capacity) {
                throw new \Exception('This time slot is full. Please select a different time.');
            }

            // Pricing
            $adultPrice = 20000; // $200.00
            $childPrice = 15000; // $150.00

            $adultTotal = ($data['adult_count'] ?? 0) * $adultPrice;
            $childTotal = ($data['child_count'] ?? 0) * $childPrice;

            // Addons
            $addonsTotal = 0;
            $addonItems = [];
            if (!empty($data['addons'])) {
                foreach ($data['addons'] as $addonId => $qty) {
                    if ($qty > 0) {
                        $addon = Addon::where('id', $addonId)->where('is_active', true)->first();
                        if ($addon) {
                            $addonsTotal += $addon->price_cents * $qty;
                            $addonItems[] = [
                                'addon' => $addon,
                                'quantity' => $qty,
                            ];
                        }
                    }
                }
            }

            $totalCents = $adultTotal + $childTotal + $addonsTotal;

            // Special occasion
            $specialOccasionAddon = null;
            foreach ($addonItems as $ai) {
                if (stripos($ai['addon']->title, 'special occasion') !== false) {
                    $specialOccasionAddon = $ai;
                    break;
                }
            }
            $isSpecialOccasion = $specialOccasionAddon !== null;

            // Fees
            $feeResult = $this->feeService->calculateFees($totalCents);
            $feesCents = $feeResult['total_fees_cents'];

            // Create booking
            $booking = Booking::create([
                'tour_date' => $data['tour_date'],
                'time_slot_id' => $data['time_slot_id'],
                'status' => 'confirmed', // Agent-created bookings are confirmed
                'source_type' => 'regular',
                'photo_upgrade_count' => 0,
                'special_occasion' => $isSpecialOccasion ? 'birthday' : null,
                'special_comment' => $data['special_comment'] ?? null,
                'total_price_cents' => $totalCents,
                'fees_cents' => $feesCents,
            ]);

            // Create primary guest
            BookingGuest::create([
                'booking_id' => $booking->id,
                'first_name' => $data['guest_first_name'],
                'last_name' => $data['guest_last_name'],
                'email' => $data['guest_email'],
                'phone' => $data['guest_phone'] ?? null,
                'is_primary' => true,
            ]);

            // Create ticket items
            if (($data['adult_count'] ?? 0) > 0) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'ticket_type' => 'adult',
                    'quantity' => $data['adult_count'],
                    'unit_price_cents' => $adultPrice,
                ]);
            }

            if (($data['child_count'] ?? 0) > 0) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'ticket_type' => 'child',
                    'quantity' => $data['child_count'],
                    'unit_price_cents' => $childPrice,
                ]);
            }

            // Create addon items
            foreach ($addonItems as $ai) {
                BookingAddon::create([
                    'booking_id' => $booking->id,
                    'addon_id' => $ai['addon']->id,
                    'quantity' => $ai['quantity'],
                    'unit_price_cents' => $ai['addon']->price_cents,
                ]);
            }

            $booking->load(['timeSlot.boat', 'primaryGuest', 'items', 'addons.addon']);

            return $booking;
        });
    }

    /**
     * Create a private tour booking on behalf of a guest.
     */
    public function createPrivateTourBooking(array $data): PrivateTourRequest
    {
        return DB::transaction(function () use ($data) {
            $adultCount = $data['adult_count'] ?? 0;
            $childCount = $data['child_count'] ?? 0;
            $totalGuests = $adultCount + $childCount;

            if ($totalGuests === 0) {
                throw new \Exception('At least one guest is required.');
            }

            // Collect selected addons
            $addonItems = [];
            if (!empty($data['addons'])) {
                foreach ($data['addons'] as $addonId => $qty) {
                    if ($qty > 0) {
                        $addon = Addon::where('id', $addonId)
                            ->where('is_active', true)
                            ->whereIn('available_for', ['private', 'both'])
                            ->first();
                        if ($addon) {
                            $addonItems[] = [
                                'addon' => $addon,
                                'quantity' => $qty,
                            ];
                        }
                    }
                }
            }

            $totalCents = $data['total_price_cents'] ?? 0;

            // Create the PrivateTourRequest
            $ptr = PrivateTourRequest::create([
                'status' => PrivateTourRequest::STATUS_CONFIRMED,
                'contact_first_name' => $data['guest_first_name'],
                'contact_last_name' => $data['guest_last_name'],
                'contact_email' => $data['guest_email'],
                'contact_phone' => $data['guest_phone'] ?? null,
                'adult_count' => $adultCount,
                'child_count' => $childCount,
                'infant_count' => $data['infant_count'] ?? 0,
                'has_occasion' => !empty($data['special_occasion']),
                'occasion_details' => $data['special_occasion'] ?? null,
                'confirmed_tour_date' => $data['tour_date'],
                'confirmed_start_time' => $data['start_time'] . ':00',
                'confirmed_end_time' => $data['end_time'] . ':00',
                'total_price_cents' => $totalCents,
                'admin_notes' => 'Created by agent',
            ]);

            // Calculate fees
            $feeResult = $this->feeService->calculateFees($totalCents);
            $feesCents = $feeResult['total_fees_cents'];

            // Create a Booking linked to this PTR
            $booking = Booking::create([
                'tour_date' => $data['tour_date'],
                'time_slot_id' => null,
                'status' => 'confirmed',
                'source_type' => 'private',
                'photo_upgrade_count' => 0,
                'special_occasion' => !empty($data['special_occasion']) ? 'birthday' : null,
                'special_comment' => $data['start_time'] . ' – ' . $data['end_time'],
                'total_price_cents' => $totalCents,
                'fees_cents' => $feesCents,
            ]);

            $ptr->update([
                'booking_id' => $booking->id,
                'fees_cents' => $feesCents,
            ]);

            // Create primary guest
            BookingGuest::create([
                'booking_id' => $booking->id,
                'first_name' => $data['guest_first_name'],
                'last_name' => $data['guest_last_name'],
                'email' => $data['guest_email'],
                'phone' => $data['guest_phone'] ?? null,
                'is_primary' => true,
            ]);

            // Create ticket items for the booking
            if ($adultCount > 0) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'ticket_type' => 'adult',
                    'quantity' => $adultCount,
                    'unit_price_cents' => $totalCents > 0 ? (int) round($totalCents * $adultCount / $totalGuests) : 0,
                ]);
            }

            if ($childCount > 0) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'ticket_type' => 'child',
                    'quantity' => $childCount,
                    'unit_price_cents' => $totalCents > 0 ? (int) round($totalCents * $childCount / $totalGuests) : 0,
                ]);
            }

            // Create addon items
            foreach ($addonItems as $ai) {
                PrivateTourAddon::create([
                    'private_tour_request_id' => $ptr->id,
                    'addon_id' => $ai['addon']->id,
                    'unit_price_cents' => $ai['addon']->private_price_cents ?? $ai['addon']->price_cents,
                ]);

                BookingAddon::create([
                    'booking_id' => $booking->id,
                    'addon_id' => $ai['addon']->id,
                    'quantity' => $ai['quantity'],
                    'unit_price_cents' => $ai['addon']->private_price_cents ?? $ai['addon']->price_cents,
                ]);
            }

            $ptr->load(['guests', 'addons.addon', 'booking']);
            $booking->load(['primaryGuest', 'items', 'addons.addon']);

            return $ptr;
        });
    }
}
