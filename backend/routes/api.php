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
use Illuminate\Support\Facades\Route;

// Public endpoints
Route::get('/gallery-photos', [GalleryPhotoController::class, 'index'])->middleware('throttle:60,1');
Route::get('/availability', AvailabilityController::class)->middleware('throttle:60,1');
Route::get('/pricing', [PricingController::class, 'index'])->middleware('throttle:60,1');
Route::get('/ticket-types', TicketTypeController::class)->middleware('throttle:60,1');
Route::get('/addons', [AddonController::class, 'index'])->middleware('throttle:60,1');
Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:60,1');
Route::post('/bookings/confirm-payment', [BookingController::class, 'confirmPayment'])->middleware('throttle:30,1');
Route::get('/bookings/lookup', [BookingController::class, 'lookup'])->middleware('throttle:30,1');
Route::get('/tickets/pdf', [TicketController::class, 'downloadPdf'])->middleware('throttle:30,1');
Route::get('/tickets/preview', [TicketController::class, 'preview'])->middleware('throttle:30,1');

// Private tour - public endpoints
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
