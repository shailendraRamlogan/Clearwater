<?php

use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\ScheduleController;
use Illuminate\Support\Facades\Route;

// Public endpoints
Route::get('/availability', AvailabilityController::class)->middleware('throttle:60,1');
Route::get('/pricing', [PricingController::class, 'index'])->middleware('throttle:60,1');
Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:60,1');
Route::post('/bookings/confirm-payment', [BookingController::class, 'confirmPayment'])->middleware('throttle:30,1');
Route::get('/bookings/lookup', [BookingController::class, 'lookup'])->middleware('throttle:30,1');
Route::get('/tickets/pdf', [TicketController::class, 'downloadPdf'])->middleware('throttle:30,1');
Route::get('/tickets/preview', [TicketController::class, 'preview'])->middleware('throttle:30,1');

// Admin endpoints (token auth)
Route::middleware(['auth.admin', 'throttle:120,1'])->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/reports/daily', [ReportController::class, 'daily']);
    Route::post('/schedules/block', [ScheduleController::class, 'block']);
    Route::post('/schedules/unblock', [ScheduleController::class, 'unblock']);
    Route::get('/schedules/blocked', [ScheduleController::class, 'blocked']);
    Route::get('/reports/schedule-pdf', [ReportController::class, 'schedulePdf']);
});
