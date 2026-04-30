<?php

namespace App\Filament\Resources\PrivateTourRequestResource\Pages;

use App\Filament\Resources\PrivateTourRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrivateTourRequests extends ListRecords
{
    protected static string $resource = PrivateTourRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
