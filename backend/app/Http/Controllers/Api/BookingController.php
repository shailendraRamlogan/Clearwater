<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Services\EmailService;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request)
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $request) {
            // Pricing
            $adultPrice = 20000; // $200 in cents
            $childPrice = 15000; // $150 in cents
            $photoUpgradePrice = 7500; // $75 per person

            $adultTotal = $validated['adult_count'] * $adultPrice;
            $childTotal = $validated['child_count'] * $childPrice;
            $upgradeCount = ($validated['package_upgrade'] ?? false)
                ? $validated['adult_count'] + $validated['child_count']
                : 0;
            $upgradeTotal = $upgradeCount * $photoUpgradePrice;
            $totalCents = $adultTotal + $childTotal + $upgradeTotal;

            $booking = Booking::create([
                'tour_date' => $validated['tour_date'],
                'time_slot_id' => $validated['time_slot_id'],
                'status' => 'pending',
                'photo_upgrade_count' => $upgradeCount,
                'special_occasion' => ($validated['special_occasion'] ?? false) ? 'birthday' : null,
                'special_comment' => $validated['special_comment'] ?? null,
                'total_price_cents' => $totalCents,
            ]);

            // Primary guest
            BookingGuest::create([
                'booking_id' => $booking->id,
                'first_name' => $validated['guest']['first_name'],
                'last_name' => $validated['guest']['last_name'],
                'email' => $validated['guest']['email'],
                'phone' => $validated['guest']['phone'],
                'is_primary' => true,
            ]);

            // Ticket items
            if ($validated['adult_count'] > 0) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'ticket_type' => 'adult',
                    'quantity' => $validated['adult_count'],
                    'unit_price_cents' => $adultPrice,
                ]);
            }

            if ($validated['child_count'] > 0) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'ticket_type' => 'child',
                    'quantity' => $validated['child_count'],
                    'unit_price_cents' => $childPrice,
                ]);
            }

            // Stripe payment intent
            $payment = null;
            $clientSecret = null;

            $stripeKey = config('services.stripe.secret');
            if ($stripeKey) {
                try {
                    \Stripe\Stripe::setApiKey($stripeKey);
                    $intent = \Stripe\PaymentIntent::create([
                        'amount' => $totalCents,
                        'currency' => 'usd',
                        'metadata' => ['booking_ref' => $booking->booking_ref],
                    ]);

                    $payment = Payment::create([
                        'booking_id' => $booking->id,
                        'stripe_intent_id' => $intent->id,
                        'amount_cents' => $totalCents,
                        'status' => 'pending',
                    ]);

                    $clientSecret = $intent->client_secret;
                } catch (\Exception $e) {
                    // Non-blocking: booking still created
                    \Log::warning('Stripe error: ' . $e->getMessage());
                }
            }

            // Load relationships for response
            $booking->load(['timeSlot.boat', 'primaryGuest', 'items']);

            // Send confirmation email (async)
            if ($payment && $payment->status === 'pending') {
                try {
                    app(EmailService::class)->sendConfirmation($booking);
                } catch (\Exception $e) {
                    \Log::warning('Email error: ' . $e->getMessage());
                }
            }

            return response()->json([
                'booking' => new BookingResource($booking),
                'payment' => $payment ? [
                    'client_secret' => $clientSecret,
                    'stripe_intent_id' => $payment->stripe_intent_id,
                ] : null,
            ], 201);
        });
    }

    public function index()
    {
        $query = Booking::with(['timeSlot.boat', 'primaryGuest', 'items']);

        if ($date = request()->query('date')) {
            $query->where('tour_date', $date);
        }

        return response()->json([
            'bookings' => BookingResource::collection($query->orderByDesc('created_at')->get()),
        ]);
    }
}
