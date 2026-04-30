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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $record->load('guests');

        // If no guests exist yet, create the primary guest from contact info
        // so the relationship repeater picks it up
        if ($record->guests->isEmpty()) {
            $record->guests()->create([
                'first_name' => $record->contact_first_name,
                'last_name' => $record->contact_last_name,
                'email' => $record->contact_email,
                'phone' => $record->contact_phone,
                'is_primary' => true,
            ]);
            $record->refresh();
        }

        return $data;
    }
}
