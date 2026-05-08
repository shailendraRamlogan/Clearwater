<?php

namespace App\Filament\Resources\AgentBookingResource\Pages;

use App\Filament\Resources\AgentBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAgentBooking extends ViewRecord
{
    protected static string $resource = AgentBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
