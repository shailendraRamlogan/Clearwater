<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ManifestExportController;

// Filament admin panel handles the root path
Route::get('/invoices/{booking}/pdf', [PdfController::class, 'download'])
    ->middleware(['web', \Filament\Http\Middleware\Authenticate::class])
    ->name('invoices.download');

// Manifest export API — uses session auth, no CSRF for fetch
Route::post('/downloadPassengerManifest', [ManifestExportController::class, 'download'])
    ->middleware(['web', \Filament\Http\Middleware\Authenticate::class])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
    ->name('manifest.export');
