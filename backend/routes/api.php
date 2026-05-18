<?php

use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\GalleryPhotoController;
use App\Http\Controllers\Api\TicketTypeController;
use App\Http\Controllers\Api\AddonController;
use App\Http\Controllers\Api\PrivateTourController;
use App\Http\Controllers\Api\Partner\BookingApiController;
use App\Http\Controllers\Api\Partner\PayoutApiController;
use App\Http\Controllers\Api\Partner\RefundApiController;
use Illuminate\Support\Facades\Route;
// Public endpoints
Route::get('/gallery-photos', [GalleryPhotoController::class, 'index'])->middleware('throttle:60,1');
Route::get('/availability', AvailabilityController::class)->middleware('throttle:60,1');
Route::get('/pricing', [PricingController::class, 'index'])->middleware('throttle:60,1');
Route::get('/ticket-types', TicketTypeController::class)->middleware('throttle:60,1');
Route::get('/addons', [AddonController::class, 'index'])->middleware('throttle:60,1');
Route::get('/private-tour-addons', [AddonController::class, 'privateTourAddons'])->middleware('throttle:60,1');
Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:60,1');
Route::post('/bookings/confirm-payment', [BookingController::class, 'confirmPayment'])->middleware('throttle:30,1');
Route::get('/bookings/lookup', [BookingController::class, 'lookup'])->middleware('throttle:30,1');
Route::get('/tickets/pdf', [TicketController::class, 'downloadPdf'])->middleware('throttle:30,1');
Route::get('/tickets/preview', [TicketController::class, 'preview'])->middleware('throttle:30,1');
// Private tour - public endpoints
Route::get('bookings/rebook-fee-confirm', [BookingController::class, 'rebookFeeConfirm'])->middleware('throttle:30,1');
Route::post('/private-tour-requests', [PrivateTourController::class, 'store'])->middleware('throttle:60,1');
Route::post('/private-tour-requests/confirm-payment', [PrivateTourController::class, 'confirmPayment'])->middleware('throttle:30,1');
Route::get('/private-tour-requests/lookup', [PrivateTourController::class, 'lookup'])->middleware('throttle:30,1');
Route::post('/private-tour-requests/{id}/initiate-payment', [PrivateTourController::class, 'initiatePayment'])->middleware('throttle:30,1');
// Admin endpoints (token auth)
Route::middleware(['auth.admin', 'throttle:120,1'])->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/reports/daily', [ReportController::class, 'daily']);
    Route::post('/schedules/block', [ScheduleController::class, 'block']);
    Route::post('/schedules/unblock', [ScheduleController::class, 'unblock']);
    Route::get('/schedules/blocked', [ScheduleController::class, 'blocked']);
    Route::get('/reports/schedule-pdf', [ReportController::class, 'schedulePdf']);
    // Private tour admin endpoints
    Route::get('/private-tour-requests', [PrivateTourController::class, 'index']);
    Route::get('/private-tour-requests/{id}', [PrivateTourController::class, 'show']);
    Route::patch('/private-tour-requests/{id}/confirm', [PrivateTourController::class, 'confirm']);
    Route::patch('/private-tour-requests/{id}/reject', [PrivateTourController::class, 'reject']);
});

// Partner CRUD API (separate token auth)
Route::middleware(['auth.partner', 'throttle:120,1'])->prefix('partner')->group(function () {
    // Bookings
    Route::get('/bookings', [BookingApiController::class, 'index']);
    Route::get('/bookings/{id}', [BookingApiController::class, 'show']);
    Route::patch('/bookings/{id}', [BookingApiController::class, 'update']);
    Route::patch('/bookings/{id}/cancel', [BookingApiController::class, 'cancel']);

    // Payouts
    Route::get('/payouts', [PayoutApiController::class, 'index']);
    Route::post('/payouts', [PayoutApiController::class, 'store']);
    Route::get('/payouts/{id}', [PayoutApiController::class, 'show']);
    Route::patch('/payouts/{id}', [PayoutApiController::class, 'update']);
    Route::patch('/payouts/{id}/confirm', [PayoutApiController::class, 'confirm']);
    Route::patch('/payouts/{id}/reject', [PayoutApiController::class, 'reject']);
    Route::delete('/payouts/{id}', [PayoutApiController::class, 'destroy']);

    // Refunds
    Route::get('/refunds', [RefundApiController::class, 'index']);
    Route::post('/refunds', [RefundApiController::class, 'store']);
    Route::get('/refunds/{id}', [RefundApiController::class, 'show']);
    Route::patch('/refunds/{id}', [RefundApiController::class, 'update']);
    Route::post('/refunds/{id}/retry', [RefundApiController::class, 'retry']);
    Route::delete('/refunds/{id}', [RefundApiController::class, 'destroy']);
});
