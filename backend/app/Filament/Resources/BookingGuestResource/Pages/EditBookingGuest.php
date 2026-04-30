<?php

namespace App\Filament\Resources\BookingGuestResource\Pages;

use App\Filament\Resources\BookingGuestResource;
use App\Services\EmailService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditBookingGuest extends EditRecord
{
    protected static string $resource = BookingGuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        Log::info('afterSave fired for guest ' . $this->record->id);
        $guest = $this->record;
        $booking = $guest->booking;

        if (!$booking) {
            Log::warning('afterSave: no booking found for guest ' . $guest->id);
            return;
        }

        $booking->loadMissing('guests', 'items');

        $totalExpected = $booking->items->sum('quantity');
        $completeGuests = $booking->guests()
            ->whereNotNull('first_name')
            ->where('first_name', '!=', '')
            ->whereNotNull('last_name')
            ->where('last_name', '!=', '')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->count();

        Log::info('afterSave check', [
            'booking' => $booking->booking_ref,
            'expected' => $totalExpected,
            'complete' => $completeGuests,
        ]);

        if ($completeGuests >= $totalExpected) {
            if ($booking->status === 'pending') {
                $booking->update(['status' => 'confirmed']);
            }

            try {
                app(EmailService::class)->sendGuestsCompletedEmail($booking);
                Notification::make()
                    ->title('Email sent to ' . $booking->primaryGuest?->email)
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Log::error('afterSave email failed', ['error' => $e->getMessage()]);
                Notification::make()
                    ->title('Failed to send completion email')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }
}
