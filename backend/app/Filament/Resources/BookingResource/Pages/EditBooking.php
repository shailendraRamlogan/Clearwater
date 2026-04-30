<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Services\EmailService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view-invoice')
                ->label('View Invoice')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->modalHeading(fn () => 'Invoice — ' . $this->record->booking_ref)
                ->modalContent(fn () => view('filament.modals.booking-invoice', ['booking' => $this->record->load(['guests', 'items', 'timeSlot.boat'])]))
                ->modalSubmitAction(fn () => Action::make('download')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(route('invoices.download', $this->record))
                    ->openUrlInNewTab()
                )
                ->modalCancelActionLabel('Close'),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $booking = $this->record;
        $booking->loadMissing('guests', 'items', 'primaryGuest');

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
                    ->title('Completion email sent to ' . $booking->primaryGuest?->email)
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
