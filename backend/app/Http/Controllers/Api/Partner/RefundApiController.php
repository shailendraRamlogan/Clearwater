<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Resources\RefundResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundApiController extends Controller
{
    /**
     * List refunds with optional filters.
     *
     * GET /api/partner/refunds
     * Query params: status, booking_id, type, date_from, date_to, per_page, page
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Refund::with(['booking', 'payment', 'initiator', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->input('booking_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
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
        $refund = Refund::with(['booking', 'payment', 'initiator', 'approver'])->find($id);

        if (!$refund) {
            return response()->json(['message' => 'Refund not found.'], 404);
        }

        return new RefundResource($refund);
    }

    /**
     * Create a refund request for a booking's payment.
     *
     * POST /api/partner/refunds
     * Body: payment_id (required), amount_cents (required), reason (required), type (optional, default: partial)
     *
     * Uses the existing RefundService for validation and Stripe processing.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => 'required|uuid',
            'amount_cents' => 'required|integer|min:1',
            'reason' => 'required|string',
            'type' => 'sometimes|string|in:full,partial',
        ]);

        $payment = Payment::find($validated['payment_id']);
        if (!$payment) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        $booking = $payment->booking;
        if (!$booking) {
            return response()->json(['message' => 'No booking associated with this payment.'], 404);
        }

        // Use a system user for partner-initiated refunds
        // The partner_user_id is set by PartnerTokenAuth middleware
        $partnerUserId = $request->attributes->get('partner_user_id', 'partner-api');

        try {
            $refundService = app(RefundService::class);

            // For agent bookings without a payment, use createAgentRefund
            if ($booking->source_type === 'agent') {
                $refund = $refundService->createAgentRefund(
                    $booking,
                    $validated['amount_cents'],
                    $validated['reason'],
                    $this->getSystemUser(),
                    $validated['type'] ?? 'partial'
                );
            } else {
                $refund = $refundService->createRefundRequest(
                    $validated['payment_id'],
                    $validated['amount_cents'],
                    $validated['reason'],
                    $this->getSystemUser(),
                    $validated['type'] ?? 'partial'
                );
            }

            return response()->json([
                'message' => 'Refund request created.',
                'refund' => new RefundResource($refund->load(['booking', 'payment', 'initiator', 'approver'])),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Partner refund creation failed', [
                'error' => $e->getMessage(),
                'payment_id' => $validated['payment_id'] ?? null,
            ]);
            return response()->json(['message' => 'Failed to create refund.'], 500);
        }
    }

    /**
     * Update refund notes/reason.
     *
     * PATCH /api/partner/refunds/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $refund = Refund::find($id);

        if (!$refund) {
            return response()->json(['message' => 'Refund not found.'], 404);
        }

        if ($refund->isTerminal()) {
            return response()->json([
                'message' => 'Cannot update a terminal refund.',
                'status' => $refund->status,
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'sometimes|string|nullable',
        ]);

        $refund->update($validated);

        return response()->json([
            'message' => 'Refund updated.',
            'refund' => new RefundResource($refund->fresh()->load(['booking', 'payment', 'initiator', 'approver'])),
        ]);
    }

    /**
     * Retry/process a failed or pending refund.
     *
     * POST /api/partner/refunds/{id}/retry
     */
    public function retry(string $id): JsonResponse
    {
        $refund = Refund::with(['booking', 'payment'])->find($id);

        if (!$refund) {
            return response()->json(['message' => 'Refund not found.'], 404);
        }

        if ($refund->status === Refund::STATUS_COMPLETED) {
            return response()->json(['message' => 'Refund already completed.'], 422);
        }

        try {
            $refundService = app(RefundService::class);

            // If approved, process it
            if ($refund->status === Refund::STATUS_APPROVED) {
                $refund = $refundService->processRefund($refund, $this->getSystemUser());
            }
            // If pending, approve first then process
            elseif ($refund->status === Refund::STATUS_PENDING) {
                $refund = $refundService->approveRefund($refund, $this->getSystemUser());
                $refund = $refundService->processRefund($refund, $this->getSystemUser());
            }
            // Processing or other states
            elseif ($refund->status === Refund::STATUS_PROCESSING) {
                $refund = $refundService->processRefund($refund, $this->getSystemUser());
            } else {
                return response()->json([
                    'message' => "Cannot retry refund in '{$refund->status}' status.",
                ], 422);
            }

            return response()->json([
                'message' => 'Refund processed.',
                'refund' => new RefundResource($refund->load(['booking', 'payment', 'initiator', 'approver'])),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'Stripe processing failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a non-terminal refund.
     *
     * DELETE /api/partner/refunds/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $refund = Refund::find($id);

        if (!$refund) {
            return response()->json(['message' => 'Refund not found.'], 404);
        }

        if ($refund->isTerminal()) {
            return response()->json([
                'message' => 'Cannot delete a terminal refund.',
                'status' => $refund->status,
            ], 422);
        }

        $refund->delete();

        return response()->json(['message' => 'Refund deleted.']);
    }

    /**
     * Get or create a system user for partner API operations.
     * Partner API uses a service account since there's no authenticated user.
     */
    private function getSystemUser()
    {
        return \App\Models\User::firstOrCreate(
            ['email' => 'partner-api@clearboatbahamas.com'],
            [
                'name' => 'Partner API',
                'password' => bcrypt(uniqid('', true)),
                'role' => 'super_admin',
            ]
        );
    }
}
