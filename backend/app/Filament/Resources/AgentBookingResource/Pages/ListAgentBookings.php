<?php

namespace App\Filament\Resources\AgentBookingResource\Pages;

use App\Filament\Resources\AgentBookingResource;
use Filament\Resources\Pages\ListRecords;

class ListAgentBookings extends ListRecords
{
    protected static string $resource = AgentBookingResource::class;

    public function getHeading(): string
    {
        return 'Agent Bookings';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
