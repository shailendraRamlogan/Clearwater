<?php

namespace App\Filament\Resources\BoatResource\Pages;

use App\Filament\Resources\BoatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBoat extends EditRecord
{
    protected static string $resource = BoatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
