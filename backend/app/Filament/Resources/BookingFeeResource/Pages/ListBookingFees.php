<?php

namespace App\Filament\Resources\BookingFeeResource\Pages;

use App\Filament\Resources\BookingFeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBookingFees extends ListRecords
{
    protected static string $resource = BookingFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
