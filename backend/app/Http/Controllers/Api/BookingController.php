<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\TimeSlot;
use App\Services\EmailService;
use App\Services\FeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request)
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $request) {
            // Capacity check with row lock
            $timeSlot = TimeSlot::where('id', $validated['time_slot_id'])->lockForUpdate()->first();
            if (!$timeSlot) {
                return response()->json(['message' => 'Time slot not found.'], 404);
            }

            $totalGuests = $validated['adult_count'] + $validated['child_count'];
            $existingBooked = Booking::where('time_slot_id', $validated['time_slot_id'])
                ->where('tour_date', $validated['tour_date'])
                ->whereNotIn('status', ['cancelled'])
                ->get()
                ->sum(fn($b) => $b->items->sum('quantity'));

            if ($existingBooked + $totalGuests > $timeSlot->max_capacity) {
                return response()->json(['message' => 'This time slot is full. Please select a different time.'], 409);
            }

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

            // Calculate fees
            $feeService = app(FeeService::class);
            $feeResult = $feeService->calculateFees($totalCents);
            $feesCents = $feeResult['total_fees_cents'];
            $grandTotalCents = $feeResult['grand_total_cents'];

            $booking = Booking::create([
                'tour_date' => $validated['tour_date'],
                'time_slot_id' => $validated['time_slot_id'],
                'status' => 'pending',
                'photo_upgrade_count' => $upgradeCount,
                'special_occasion' => ($validated['special_occasion'] ?? false) ? 'birthday' : null,
                'special_comment' => $validated['special_comment'] ?? null,
                'total_price_cents' => $totalCents,
                'fees_cents' => $feesCents,
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

            // Additional guests (optional)
            if (!empty($validated['guests'])) {
                foreach ($validated['guests'] as $guest) {
                    if (!empty($guest['first_name']) || !empty($guest['last_name']) || !empty($guest['email'])) {
                        BookingGuest::create([
                            'booking_id' => $booking->id,
                            'first_name' => $guest['first_name'],
                            'last_name' => $guest['last_name'] ?? '',
                            'email' => $guest['email'] ?? '',
                            'phone' => '',
                            'is_primary' => false,
                        ]);
                    }
                }
            }

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
                        'amount' => $grandTotalCents,
                        'currency' => 'usd',
                        'description' => 'Clear Boat Booking - ' . ($booking->primaryGuest->first_name ?? 'Guest'),
                        'statement_descriptor' => 'Clear Boat Booking',
                        'metadata' => ['booking_ref' => $booking->booking_ref],
                    ]);

                    $payment = Payment::create([
                        'booking_id' => $booking->id,
                        'stripe_intent_id' => $intent->id,
                        'amount_cents' => $grandTotalCents,
                        'status' => 'pending',
                    ]);

                    $clientSecret = $intent->client_secret;
                } catch (\Exception $e) {
                    // Non-blocking: booking still created
                    \Log::warning('Stripe error: ' . $e->getMessage());
                }
            }

            // Auto-confirm if all guest info is complete
            $totalTickets = $validated['adult_count'] + $validated['child_count'];
            $completeGuestCount = $booking->guests()
                ->whereNotNull('first_name')->where('first_name', '!=', '')
                ->whereNotNull('last_name')->where('last_name', '!=', '')
                ->whereNotNull('email')->where('email', '!=', '')
                ->count();

            if ($completeGuestCount >= $totalTickets) {
                $booking->update(['status' => 'confirmed']);
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
                'fees' => $feeResult['fees'],
                'payment' => $payment ? [
                    'client_secret' => $clientSecret,
                    'stripe_intent_id' => $payment->stripe_intent_id,
                ] : null,
            ], 201);
        });
    }

    public function lookup(Request $request)
    {
        $request->validate([
            'email' => 'nullable|email',
            'ref' => 'required|string',
        ]);

        $query = Booking::where('booking_ref', $request->query('ref'));

        if ($request->filled('email')) {
            $query->whereHas('primaryGuest', fn($q) => $q->where('email', $request->query('email')));
        }

        $booking = $query
            ->with(['timeSlot.boat', 'primaryGuest', 'items'])
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        return response()->json(['booking' => new BookingResource($booking)]);
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

    public function confirmPayment(Request $request)
    {
        $request->validate([
            'booking_ref' => 'required|string',
            'payment_intent_id' => 'required|string',
        ]);

        $payment = Payment::where('stripe_intent_id', $request->payment_intent_id)
            ->whereHas('booking', fn ($q) => $q->where('booking_ref', $request->booking_ref))
            ->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        // Verify with Stripe that the payment succeeded
        $stripeKey = config('services.stripe.secret');
        if ($stripeKey) {
            try {
                \Stripe\Stripe::setApiKey($stripeKey);
                $intent = \Stripe\PaymentIntent::retrieve($request->payment_intent_id);

                if ($intent->status === 'succeeded') {
                    $payment->update(['status' => 'succeeded']);
                    $payment->booking->update(['status' => 'confirmed']);
                } elseif ($intent->status === 'requires_payment_method') {
                    $payment->update(['status' => 'failed']);
                    return response()->json(['message' => 'Payment failed.'], 400);
                }
            } catch (\Exception $e) {
                \Log::warning('Stripe verify error: ' . $e->getMessage());
            }
        }

        return response()->json(['status' => $payment->fresh()->status]);
    }
}
