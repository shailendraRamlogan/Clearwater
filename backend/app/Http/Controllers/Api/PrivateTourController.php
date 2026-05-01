<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrivateTourRequest;
use App\Http\Requests\ConfirmPrivateTourRequest;
use App\Models\Addon;
use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\BookingGuest;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\PrivateTourAddon;
use App\Models\PrivateTourGuest;
use App\Models\PrivateTourRequest;
use App\Services\EmailService;
use App\Services\FeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivateTourController extends Controller
{
    public function store(StorePrivateTourRequest $request)
    {
        $validated = $request->validated();

        $privateTourRequest = DB::transaction(function () use ($validated) {
            $ptr = PrivateTourRequest::create([
                'contact_first_name' => $validated['contact_first_name'],
                'contact_last_name' => $validated['contact_last_name'],
                'contact_email' => $validated['contact_email'],
                'contact_phone' => $validated['contact_phone'],
                'adult_count' => $validated['adult_count'],
                'child_count' => $validated['child_count'],
                'infant_count' => $validated['infant_count'],
                'has_occasion' => $validated['has_occasion'] ?? false,
                'occasion_details' => $validated['occasion_details'] ?? null,
            ]);

            foreach ($validated['preferred_dates'] as $index => $dateInput) {
                $ptr->preferredDates()->create([
                    'date' => $dateInput['date'],
                    'time_preference' => $dateInput['time_preference'],
                    'sort_order' => $index,
                ]);
            }

            // Attach selected addons (no prices yet — admin sets them)
            foreach ($validated['addon_ids'] ?? [] as $addonId) {
                $ptr->addons()->create([
                    'addon_id' => $addonId,
                    'unit_price_cents' => null,
                ]);
            }

            return $ptr;
        });

        // Send email after transaction
        try {
            app(EmailService::class)->sendPrivateTourRequestReceived($privateTourRequest);
        } catch (\Exception $e) {
            \Log::warning("Private tour request email error: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Private tour request submitted successfully.',
            'booking_ref' => $privateTourRequest->booking_ref,
            'request' => $privateTourRequest->load(['preferredDates', 'addons.addon']),
        ], 201);
    }

    public function availableAddons()
    {
        $addons = Addon::active()
            ->forPrivateTours()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'description' => $a->description,
                'icon_name' => $a->icon_name,
            ]);

        return response()->json(['addons' => $addons]);
    }

    public function index(Request $request)
    {
        $query = PrivateTourRequest::with(['preferredDates', 'guests', 'addons.addon']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'requests' => $query->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function show(PrivateTourRequest $privateTourRequest)
    {
        $privateTourRequest->load(['preferredDates', 'guests', 'addons.addon', 'booking']);

        return response()->json([
            'request' => $privateTourRequest,
        ]);
    }

    public function confirm(ConfirmPrivateTourRequest $request, PrivateTourRequest $privateTourRequest)
    {
        if ($privateTourRequest->status !== PrivateTourRequest::STATUS_REQUESTED) {
            return response()->json(['message' => 'This request cannot be confirmed.'], 422);
        }

        $validated = $request->validated();

        // Calculate fees
        $feeService = app(FeeService::class);
        $feeResult = $feeService->calculateFees($validated['total_price_cents']);

        DB::transaction(function () use ($privateTourRequest, $validated, $feeResult) {
            $privateTourRequest->update([
                'status' => PrivateTourRequest::STATUS_CONFIRMED,
                'confirmed_tour_date' => $validated['confirmed_tour_date'],
                'confirmed_start_time' => $validated['confirmed_start_time'],
                'confirmed_end_time' => $validated['confirmed_end_time'],
                'total_price_cents' => $validated['total_price_cents'],
                'fees_cents' => $feeResult['total_fees_cents'],
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            // Clear existing guests and create new ones
            $privateTourRequest->guests()->delete();

            foreach ($validated['guests'] as $guestInput) {
                $privateTourRequest->guests()->create([
                    'first_name' => $guestInput['first_name'],
                    'last_name' => $guestInput['last_name'],
                    'email' => $guestInput['email'] ?? null,
                    'phone' => $guestInput['phone'] ?? null,
                    'is_primary' => $guestInput['is_primary'] ?? false,
                ]);
            }

            // Update addon prices if provided
            if (isset($validated['addon_prices'])) {
                foreach ($validated['addon_prices'] as $addonId => $priceCents) {
                    PrivateTourAddon::where('private_tour_request_id', $privateTourRequest->id)
                        ->where('addon_id', $addonId)
                        ->update(['unit_price_cents' => $priceCents !== null ? (int) $priceCents : null]);
                }
            }
        });

        $privateTourRequest->refresh()->load(['preferredDates', 'guests', 'addons.addon']);

        // Send confirmation email
        try {
            app(EmailService::class)->sendPrivateTourConfirmed($privateTourRequest);
        } catch (\Exception $e) {
            \Log::warning("Private tour confirm email error: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Private tour confirmed. Quote sent to customer.',
            'request' => $privateTourRequest,
        ]);
    }

    public function reject(Request $request, PrivateTourRequest $privateTourRequest)
    {
        if ($privateTourRequest->status !== PrivateTourRequest::STATUS_REQUESTED) {
            return response()->json(['message' => 'This request cannot be rejected.'], 422);
        }

        $validated = $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        $privateTourRequest->update([
            'status' => PrivateTourRequest::STATUS_REJECTED,
            'admin_notes' => $validated['admin_notes'],
        ]);

        // Send rejection email
        try {
            app(EmailService::class)->sendPrivateTourRejected($privateTourRequest);
        } catch (\Exception $e) {
            \Log::warning("Private tour reject email error: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Private tour request rejected.',
            'request' => $privateTourRequest,
        ]);
    }

    public function initiatePayment(Request $request, string $id)
    {
        // Lookup by booking_ref first, then by UUID
        $privateTourRequest = PrivateTourRequest::where('booking_ref', $id)->first();
        if (!$privateTourRequest) {
            $privateTourRequest = PrivateTourRequest::where('id', $id)->firstOrFail();
        }
        if (!$privateTourRequest) {
            abort(404, 'Private tour request not found.');
        }

        if (!in_array($privateTourRequest->status, [PrivateTourRequest::STATUS_AWAITING_PAYMENT, PrivateTourRequest::STATUS_CONFIRMED])) {
            return response()->json(['message' => 'Payment can only be initiated for requests that have been quoted.'], 422);
        }

        $stripeKey = config('services.stripe.secret');
        if (!$stripeKey) {
            return response()->json(['message' => 'Payment system not configured.'], 500);
        }

        $grandTotal = $privateTourRequest->grand_total;

        try {
            \Stripe\Stripe::setApiKey($stripeKey);
            $intent = \Stripe\PaymentIntent::create([
                'amount' => $grandTotal,
                'currency' => 'usd',
                'description' => "Private Tour - {$privateTourRequest->booking_ref}",
                'metadata' => [
                    'private_tour_ref' => $privateTourRequest->booking_ref,
                    'customer_email' => $privateTourRequest->contact_email,
                ],
            ]);

            $privateTourRequest->update([
                'status' => PrivateTourRequest::STATUS_AWAITING_PAYMENT,
                'stripe_intent_id' => $intent->id,
            ]);

            return response()->json([
                'client_secret' => $intent->client_secret,
                'stripe_intent_id' => $intent->id,
                'amount' => $grandTotal,
            ]);
        } catch (\Exception $e) {
            \Log::warning("Private tour Stripe error: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create payment. Please try again.'], 500);
        }
    }

    public function confirmPayment(Request $request)
    {
        $validated = $request->validate([
            'booking_ref' => 'required|string',
            'payment_intent_id' => 'required|string',
        ]);

        $privateTourRequest = PrivateTourRequest::where('booking_ref', $validated['booking_ref'])->first();

        if (!$privateTourRequest || $privateTourRequest->stripe_intent_id !== $validated['payment_intent_id']) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        if ($privateTourRequest->status !== PrivateTourRequest::STATUS_AWAITING_PAYMENT) {
            return response()->json(['message' => 'Invalid request state.'], 422);
        }

        $stripeKey = config('services.stripe.secret');
        if (!$stripeKey) {
            return response()->json(['message' => 'Payment system not configured.'], 500);
        }

        try {
            \Stripe\Stripe::setApiKey($stripeKey);
            $intent = \Stripe\PaymentIntent::retrieve($validated['payment_intent_id']);

            if ($intent->status !== 'succeeded') {
                if ($intent->status === 'requires_payment_method') {
                    return response()->json(['message' => 'Payment failed.'], 400);
                }
                return response()->json(['message' => 'Payment is still processing.'], 400);
            }
        } catch (\Exception $e) {
            \Log::warning("Private tour payment verify error: " . $e->getMessage());
            return response()->json(['message' => 'Failed to verify payment.'], 500);
        }

        // Convert to regular booking
        $booking = DB::transaction(function () use ($privateTourRequest, $validated) {
            // Create the regular booking (no time_slot_id for private tours)
            $booking = Booking::create([
                'tour_date' => $privateTourRequest->confirmed_tour_date,
                'time_slot_id' => null,
                'status' => 'confirmed',
                'source_type' => 'private',
                'photo_upgrade_count' => 0,
                'special_occasion' => $privateTourRequest->has_occasion ? 'other' : null,
                'special_comment' => 'Private Tour — ' . $privateTourRequest->formatted_time,
                'total_price_cents' => $privateTourRequest->total_price_cents,
                'fees_cents' => $privateTourRequest->fees_cents,
            ]);

            // Copy guests from private tour guests to booking guests
            $privateTourGuests = $privateTourRequest->guests;
            foreach ($privateTourGuests as $guest) {
                BookingGuest::create([
                    'booking_id' => $booking->id,
                    'first_name' => $guest->first_name,
                    'last_name' => $guest->last_name,
                    'email' => $guest->email ?? '',
                    'phone' => $guest->phone ?? '',
                    'is_primary' => $guest->is_primary,
                ]);
            }

            // Create booking item for private tour (flat rate)
            BookingItem::create([
                'booking_id' => $booking->id,
                'ticket_type' => 'private_tour',
                'quantity' => 1,
                'unit_price_cents' => $privateTourRequest->total_price_cents,
            ]);

            // Copy addons to booking_addons
            foreach ($privateTourRequest->addons as $privateTourAddon) {
                BookingAddon::create([
                    'booking_id' => $booking->id,
                    'addon_id' => $privateTourAddon->addon_id,
                    'quantity' => 1,
                    'unit_price_cents' => $privateTourAddon->unit_price_cents ?? 0,
                ]);
            }

            // Create payment record
            Payment::create([
                'booking_id' => $booking->id,
                'stripe_intent_id' => $validated['payment_intent_id'],
                'amount_cents' => $privateTourRequest->grand_total,
                'status' => 'succeeded',
                'metadata' => [
                    'private_tour_ref' => $privateTourRequest->booking_ref,
                ],
            ]);

            // Update private tour request status
            $privateTourRequest->update([
                'status' => PrivateTourRequest::STATUS_COMPLETED,
            ]);

            return $booking;
        });

        // Send confirmation email
        try {
            app(EmailService::class)->sendPrivateTourPaymentSucceeded($privateTourRequest->fresh());
        } catch (\Exception $e) {
            \Log::warning("Private tour payment success email error: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Payment successful! Your private tour is booked.',
            'status' => 'succeeded',
            'booking_ref' => $booking->booking_ref,
        ]);
    }

    public function lookup(Request $request)
    {
        $request->validate([
            'ref' => 'required|string',
        ]);

        $privateTourRequest = PrivateTourRequest::where('booking_ref', $request->query('ref'))
            ->with(['preferredDates', 'guests', 'addons.addon'])
            ->first();

        if (!$privateTourRequest) {
            return response()->json(['message' => 'Request not found.'], 404);
        }

        return response()->json([
            'request' => $privateTourRequest,
        ]);
    }
}
