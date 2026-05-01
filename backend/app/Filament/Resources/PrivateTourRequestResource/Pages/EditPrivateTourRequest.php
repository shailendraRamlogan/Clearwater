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
        $record->load(['guests', 'addons']);

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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $selectedAddonIds = $this->data['selected_addon_ids'] ?? [];

        // Get currently attached addon IDs
        $currentAddonIds = $record->addons()->pluck('addon_id')->toArray();

        // Addons to remove
        $toRemove = array_diff($currentAddonIds, $selectedAddonIds);
        foreach ($toRemove as $addonId) {
            $record->addons()->where('addon_id', $addonId)->delete();
        }

        // Addons to add
        $toAdd = array_diff($selectedAddonIds, $currentAddonIds);
        foreach ($toAdd as $addonId) {
            $record->addons()->create([
                'addon_id' => $addonId,
                'unit_price_cents' => null,
            ]);
        }
    }
}
