<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Resources\RefundResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class RefundApiController extends Controller
{
    /**
     * List refunds with optional filters.
     *
     * GET /api/partner/refunds
     * Query params: status, booking_id, date_from, date_to, per_page, page
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Refund::with(['booking', 'payment']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->input('booking_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        $perPage = min($request->input('per_page', 25), 100);

        $refunds = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return RefundResource::collection($refunds);
    }

    /**
     * Get a single refund.
     *
     * GET /api/partner/refunds/{id}
     */
    public function show(string $id): RefundResource|JsonResponse
    {
        $refund = Refund::with(['booking', 'payment'])->find($id);

        if (!$refund) {
            return response()->json(['message' => 'Refund not found.'], 404);
        }

        return new RefundResource($refund);
    }

    /**
     * Create a refund for a booking.
     *
     * POST /api/partner/refunds
     * Body: booking_id (required), amount_cents (required), reason, notes
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|uuid',
            'amount_cents' => 'required|integer|min:1',
            'reason' => 'sometimes|string|nullable|in:customer_request,duplicate,fraudulent,other',
            'notes' => 'sometimes|string|nullable',
        ]);

        $booking = Booking::find($validated['booking_id']);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        // Find a successful payment for this booking
        $payment = $booking->payments()->where('status', 'succeeded')->first();
        if (!$payment) {
            return response()->json([
                'message' => 'No successful payment found for this booking.',
            ], 422);
        }

        // Validate refund amount doesn't exceed payment
        $alreadyRefunded = Refund::where('payment_id', $payment->id)
            ->where('status', '!=', 'failed')
            ->sum('amount_cents');

        if ($validated['amount_cents'] > ($payment->amount_cents - $alreadyRefunded)) {
            return response()->json([
                'message' => 'Refund amount exceeds available amount.',
                'payment_amount_cents' => $payment->amount_cents,
                'already_refunded_cents' => $alreadyRefunded,
                'available_cents' => $payment->amount_cents - $alreadyRefunded,
            ], 422);
        }

        return DB::transaction(function () use ($validated, $booking, $payment, $request) {
            $refund = Refund::create([
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'amount_cents' => $validated['amount_cents'],
                'reason' => $validated['reason'] ?? 'other',
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'initiated_by' => $request->attributes->get('partner_user_id', 'partner-api'),
            ]);

            // Process Stripe refund if Stripe is configured
            $stripeKey = config('services.stripe.secret');
            if ($stripeKey && $payment->stripe_intent_id) {
                try {
                    \Stripe\Stripe::setApiKey($stripeKey);

                    // Retrieve the PaymentIntent to get the charge ID
                    $intent = \Stripe\PaymentIntent::retrieve($payment->stripe_intent_id);
                    $chargeId = $intent->latest_charge;

                    if ($chargeId) {
                        $stripeRefund = \Stripe\Refund::create([
                            'charge' => $chargeId,
                            'amount' => $validated['amount_cents'],
                            'reason' => $validated['reason'] ?? 'requested_by_customer',
                            'metadata' => [
                                'booking_ref' => $booking->booking_ref,
                                'refund_id' => $refund->id,
                            ],
                        ]);

                        $refund->update([
                            'stripe_refund_id' => $stripeRefund->id,
                            'status' => 'processed',
                        ]);

                        // If full refund, cancel the booking
                        if ($validated['amount_cents'] >= $payment->amount_cents) {
                            $booking->update(['status' => Booking::STATUS_CANCELLED]);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Stripe refund error: ' . $e->getMessage());
                    // Leave as pending — can be retried manually
                }
            }

            return response()->json([
                'message' => 'Refund created.',
                'refund' => new RefundResource($refund->fresh()->load(['booking', 'payment'])),
            ], 201);
        });
    }

    /**
     * Update refund notes.
     *
     * PATCH /api/partner/refunds/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $refund = Refund::find($id);

        if (!$refund) {
            return response()->json(['message' => 'Refund not found.'], 404);
        }

        $validated = $request->validate([
            'notes' => 'sometimes|string|nullable',
        ]);

        $refund->update($validated);

        return response()->json([
            'message' => 'Refund updated.',
            'refund' => new RefundResource($refund->fresh()->load(['booking', 'payment'])),
        ]);
    }

    /**
     * Retry a failed/pending refund via Stripe.
     *
     * POST /api/partner/refunds/{id}/retry
     */
    public function retry(string $id): JsonResponse
    {
        $refund = Refund::with(['booking', 'payment'])->find($id);

        if (!$refund) {
            return response()->json(['message' => 'Refund not found.'], 404);
        }

        if ($refund->status === 'processed') {
            return response()->json(['message' => 'Refund already processed.'], 422);
        }

        $payment = $refund->payment;
        $stripeKey = config('services.stripe.secret');

        if (!$stripeKey || !$payment->stripe_intent_id) {
            return response()->json(['message' => 'Stripe not configured or no payment intent.'], 422);
        }

        try {
            \Stripe\Stripe::setApiKey($stripeKey);
            $intent = \Stripe\PaymentIntent::retrieve($payment->stripe_intent_id);
            $chargeId = $intent->latest_charge;

            if ($chargeId) {
                $stripeRefund = \Stripe\Refund::create([
                    'charge' => $chargeId,
                    'amount' => $refund->amount_cents,
                    'metadata' => [
                        'booking_ref' => $refund->booking->booking_ref,
                        'refund_id' => $refund->id,
                        'retry' => true,
                    ],
                ]);

                $refund->update([
                    'stripe_refund_id' => $stripeRefund->id,
                    'status' => 'processed',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Stripe refund failed.',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Refund processed.',
            'refund' => new RefundResource($refund->fresh()->load(['booking', 'payment'])),
        ]);
    }

    /**
     * Delete a pending/failed refund.
     *
     * DELETE /api/partner/refunds/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $refund = Refund::find($id);

        if (!$refund) {
            return response()->json(['message' => 'Refund not found.'], 404);
        }

        if ($refund->status === 'processed') {
            return response()->json([
                'message' => 'Processed refunds cannot be deleted.',
            ], 422);
        }

        $refund->delete();

        return response()->json(['message' => 'Refund deleted.']);
    }
}
