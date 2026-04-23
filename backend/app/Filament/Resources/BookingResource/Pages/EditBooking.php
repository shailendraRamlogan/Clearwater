<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Livewire\GuestEditor;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            \Filament\Forms\Components\Section::make('Guest Details Editor')
                ->schema([
                    \Filament\Forms\Components\LivewireComponent::make(GuestEditor::class),
                ])
                ->visibleOn('edit'),
        ];
    }
}
