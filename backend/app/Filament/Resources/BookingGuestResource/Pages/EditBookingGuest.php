<?php

namespace App\Filament\Resources\BookingGuestResource\Pages;

use App\Filament\Resources\BookingGuestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBookingGuest extends EditRecord
{
    protected static string $resource = BookingGuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
