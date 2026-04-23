<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
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
}
