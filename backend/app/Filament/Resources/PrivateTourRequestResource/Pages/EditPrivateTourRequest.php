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

        // If no guests exist yet, prefill first guest from contact info
        if ($record->guests->isEmpty()) {
            $data['guests'] = [
                [
                    'first_name' => $record->contact_first_name,
                    'last_name' => $record->contact_last_name,
                    'email' => $record->contact_email,
                    'phone' => $record->contact_phone,
                    'is_primary' => true,
                ],
            ];
        }

        return $data;
    }
}
