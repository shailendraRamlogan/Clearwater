<?php

namespace App\Filament\Resources\AgentBookingResource\Pages;

use App\Filament\Resources\AgentBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgentBooking extends EditRecord
{
    protected static string $resource = AgentBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
