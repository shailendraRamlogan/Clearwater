<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartnerBookingResource;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookingApiController extends Controller
{
    /**
     * List bookings with optional filters.
     *
     * GET /api/partner/bookings
     * Query params: status, tour_date, tour_date_from, tour_date_to, booking_ref, per_page, page
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Booking::with(['timeSlot', 'guests', 'items', 'payments', 'addons']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('tour_date')) {
            $query->where('tour_date', $request->input('tour_date'));
        }

        if ($request->filled('tour_date_from')) {
            $query->where('tour_date', '>=', $request->input('tour_date_from'));
        }

        if ($request->filled('tour_date_to')) {
            $query->where('tour_date', '<=', $request->input('tour_date_to'));
        }

        if ($request->filled('booking_ref')) {
            $query->where('booking_ref', 'ilike', '%' . $request->input('booking_ref') . '%');
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->input('source_type'));
        }

        $perPage = min($request->input('per_page', 25), 100);

        $bookings = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return PartnerBookingResource::collection($bookings);
    }

    /**
     * Get a single booking by ID.
     *
     * GET /api/partner/bookings/{id}
     */
    public function show(string $id): PartnerBookingResource|JsonResponse
    {
        $booking = Booking::with(['timeSlot.boat', 'guests', 'items', 'payments', 'addons.addon'])
            ->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        return new PartnerBookingResource($booking);
    }

    /**
     * Cancel a booking.
     *
     * PATCH /api/partner/bookings/{id}/cancel
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        if (!$booking->canTransitionTo(Booking::STATUS_CANCELLED)) {
            return response()->json([
                'message' => 'Booking cannot be cancelled.',
                'current_status' => $booking->status,
            ], 422);
        }

        $booking->update(['status' => Booking::STATUS_CANCELLED]);

        return response()->json([
            'message' => 'Booking cancelled.',
            'booking' => new PartnerBookingResource($booking->fresh()->load(['timeSlot', 'guests', 'items', 'payments', 'addons'])),
        ]);
    }

    /**
     * Update booking details.
     *
     * PATCH /api/partner/bookings/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $validated = $request->validate([
            'special_comment' => 'sometimes|string|nullable',
            'special_occasion' => 'sometimes|string|nullable',
            'notes' => 'sometimes|string|nullable',
        ]);

        $booking->update($validated);

        return response()->json([
            'message' => 'Booking updated.',
            'booking' => new PartnerBookingResource($booking->fresh()->load(['timeSlot', 'guests', 'items', 'payments', 'addons'])),
        ]);
    }
}
