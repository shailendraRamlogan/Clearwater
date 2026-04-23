<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ConfirmationRequiredWidget extends BaseWidget
{
    protected static ?string $heading = 'Confirmation Required';
    protected static ?int $sort = 21;
    protected int | string | array $columnSpan = 'full';

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
                Tables\Actions\Action::make('edit')
                    ->url(fn (Booking $record) => route('filament.admin.resources.bookings.edit', $record)),
            ])
            ->emptyStateHeading('No bookings require confirmation');
    }
}
