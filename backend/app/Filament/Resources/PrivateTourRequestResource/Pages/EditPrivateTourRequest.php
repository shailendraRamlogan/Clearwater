<?php

namespace App\Filament\Resources\PrivateTourRequestResource\Pages;

use App\Filament\Resources\PrivateTourRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrivateTourRequest extends EditRecord
{
    protected static string $resource = PrivateTourRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->status === 'rejected'),
        ];
    }
}
