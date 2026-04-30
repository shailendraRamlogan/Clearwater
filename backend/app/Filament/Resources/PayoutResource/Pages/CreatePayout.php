<?php

namespace App\Filament\Resources\PayoutResource\Pages;

use App\Filament\Resources\PayoutResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayout extends CreateRecord
{
    protected static string $resource = PayoutResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['amount_dollars'])) {
            $data['amount_cents'] = (int) round((float) $data['amount_dollars'] * 100);
        }
        unset($data['amount_dollars']);
        unset($data['initiated_by_name']);

        $data['status'] = 'pending';
        $data['initiated_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return PayoutResource::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Initiate Payout')
                ->disabled(fn () => auth()->user()?->role !== 'super_admin'),
            $this->getCancelFormAction(),
        ];
    }
}
