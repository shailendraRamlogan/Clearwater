<?php

namespace App\Filament\Resources\BookingGuestResource\Pages;

use App\Filament\Resources\BookingGuestResource;
use App\Services\EmailService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBookingGuest extends CreateRecord
{
    protected static string $resource = BookingGuestResource::class;

    protected function afterCreate(): void
    {
        \Illuminate\Support\Facades\Log::info('afterCreate fired for guest ' . $this->record->id);
        $guest = $this->record;
        $booking = $guest->booking;

        if (!$booking) {
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
                Notification::make()
                    ->title('Failed to send completion email')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }
}
