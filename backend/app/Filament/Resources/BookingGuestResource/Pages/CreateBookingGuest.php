<?php

namespace App\Filament\Resources\BookingGuestResource\Pages;

use App\Filament\Resources\BookingGuestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBookingGuest extends CreateRecord
{
    protected static string $resource = BookingGuestResource::class;
}
