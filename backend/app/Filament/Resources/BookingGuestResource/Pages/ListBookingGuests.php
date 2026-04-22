<?php

namespace App\Filament\Resources\BookingGuestResource\Pages;

use App\Filament\Resources\BookingGuestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBookingGuests extends ListRecords
{
    protected static string $resource = BookingGuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
