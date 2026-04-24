<?php

namespace App\Filament\Resources\BookingFeeResource\Pages;

use App\Filament\Resources\BookingFeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBookingFee extends EditRecord
{
    protected static string $resource = BookingFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
