<?php

namespace App\Filament\Resources\BookingAgentResource\Pages;

use App\Filament\Resources\BookingAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBookingAgents extends ListRecords
{
    protected static string $resource = BookingAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
