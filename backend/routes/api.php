<?php

use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ScheduleController;
use Illuminate\Support\Facades\Route;

// Public endpoints
Route::get('/availability', AvailabilityController::class);
Route::post('/bookings', [BookingController::class, 'store']);

// Admin endpoints (token auth)
Route::middleware('auth.admin')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/reports/daily', [ReportController::class, 'daily']);
    Route::post('/schedules/block', [ScheduleController::class, 'block']);
    Route::post('/schedules/unblock', [ScheduleController::class, 'unblock']);
    Route::get('/reports/schedule-pdf', [ReportController::class, 'schedulePdf']);
});
