<?php

namespace App\Filament\Resources\BookingAgentResource\Pages;

use App\Filament\Resources\BookingAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBookingAgent extends EditRecord
{
    protected static string $resource = BookingAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
