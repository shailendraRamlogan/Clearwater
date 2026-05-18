<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayoutResource;
use App\Models\Payout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PayoutApiController extends Controller
{
    /**
     * List payouts with optional filters.
     *
     * GET /api/partner/payouts
     * Query params: status, date_from, date_to, per_page, page
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Payout::with(['initiator', 'confirmer', 'rejecter']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        $perPage = min($request->input('per_page', 25), 100);

        $payouts = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return PayoutResource::collection($payouts);
    }

    /**
     * Get a single payout.
     *
     * GET /api/partner/payouts/{id}
     */
    public function show(string $id): PayoutResource|JsonResponse
    {
        $payout = Payout::with(['initiator', 'confirmer', 'rejecter'])->find($id);

        if (!$payout) {
            return response()->json(['message' => 'Payout not found.'], 404);
        }

        return new PayoutResource($payout);
    }

    /**
     * Create a new payout.
     *
     * POST /api/partner/payouts
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount_cents' => 'required|integer|min:1',
            'notes' => 'sometimes|string|nullable',
            'transfer_name' => 'sometimes|string|nullable',
        ]);

        $payout = Payout::create([
            'amount_cents' => $validated['amount_cents'],
            'status' => 'pending',
            'initiated_by' => $request->attributes->get('partner_user_id'),
            'notes' => $validated['notes'] ?? null,
            'transfer_name' => $validated['transfer_name'] ?? null,
        ]);

        return response()->json([
            'message' => 'Payout created.',
            'payout' => new PayoutResource($payout->load(['initiator'])),
        ], 201);
    }

    /**
     * Update a payout (notes, transfer_name).
     *
     * PATCH /api/partner/payouts/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $payout = Payout::find($id);

        if (!$payout) {
            return response()->json(['message' => 'Payout not found.'], 404);
        }

        $validated = $request->validate([
            'notes' => 'sometimes|string|nullable',
            'transfer_name' => 'sometimes|string|nullable',
        ]);

        $payout->update($validated);

        return response()->json([
            'message' => 'Payout updated.',
            'payout' => new PayoutResource($payout->fresh()->load(['initiator', 'confirmer', 'rejecter'])),
        ]);
    }

    /**
     * Confirm a pending payout.
     *
     * PATCH /api/partner/payouts/{id}/confirm
     */
    public function confirm(Request $request, string $id): JsonResponse
    {
        $payout = Payout::find($id);

        if (!$payout) {
            return response()->json(['message' => 'Payout not found.'], 404);
        }

        if ($payout->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending payouts can be confirmed.',
                'current_status' => $payout->status,
            ], 422);
        }

        $payout->update([
            'status' => 'confirmed',
            'confirmed_by' => $request->attributes->get('partner_user_id'),
            'confirmed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payout confirmed.',
            'payout' => new PayoutResource($payout->fresh()->load(['initiator', 'confirmer', 'rejecter'])),
        ]);
    }

    /**
     * Reject a pending payout.
     *
     * PATCH /api/partner/payouts/{id}/reject
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $payout = Payout::find($id);

        if (!$payout) {
            return response()->json(['message' => 'Payout not found.'], 404);
        }

        if ($payout->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending payouts can be rejected.',
                'current_status' => $payout->status,
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'sometimes|string|nullable',
        ]);

        $payout->update([
            'status' => 'rejected',
            'rejected_by' => $request->attributes->get('partner_user_id'),
            'rejected_at' => now(),
            'notes' => ($payout->notes ? $payout->notes . "\n" : '') . 'Rejected: ' . ($validated['reason'] ?? 'No reason provided'),
        ]);

        return response()->json([
            'message' => 'Payout rejected.',
            'payout' => new PayoutResource($payout->fresh()->load(['initiator', 'confirmer', 'rejecter'])),
        ]);
    }

    /**
     * Delete a pending payout.
     *
     * DELETE /api/partner/payouts/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $payout = Payout::find($id);

        if (!$payout) {
            return response()->json(['message' => 'Payout not found.'], 404);
        }

        if ($payout->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending payouts can be deleted.',
                'current_status' => $payout->status,
            ], 422);
        }

        $payout->delete();

        return response()->json(['message' => 'Payout deleted.']);
    }
}
