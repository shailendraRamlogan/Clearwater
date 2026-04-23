<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ConfirmationRequired extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationLabel = 'Needs Confirmation';
    protected static ?int $navigationSort = 32;
    protected static string $view = 'filament.pages.confirmation-required';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->needsConfirmation()
                    ->with(['primaryGuest', 'timeSlot'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('booking_ref')->searchable(),
                Tables\Columns\TextColumn::make('primaryGuest.full_name')->label('Purchaser')->default('—'),
                Tables\Columns\TextColumn::make('tour_date')->date(),
                Tables\Columns\TextColumn::make('reason')
                    ->default('Duplicate guest data detected'),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Booking $record) {
                        $record->update(['is_confirmed' => true]);
                        Notification::make()
                            ->title('Booking confirmed')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->url(fn (Booking $record) => route('filament.admin.resources.bookings.edit', $record)),
            ])
            ->emptyStateHeading('No bookings require confirmation');
    }
}
