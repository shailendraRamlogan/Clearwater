<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class IncompleteBookingsWidget extends BaseWidget
{
    protected static ?string $heading = 'Incomplete Guest Details';
    protected static ?int $sort = 20;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->withCount('guests')
                    ->with(['primaryGuest', 'timeSlot.boat', 'items'])
                    ->whereHas('items', function ($q) {
                        $q->selectRaw('booking_id')
                          ->groupBy('booking_id')
                          ->havingRaw('SUM(quantity) > (SELECT COUNT(*) FROM booking_guests WHERE booking_guests.booking_id = bookings.id)');
                    })
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('booking_ref')->searchable(),
                Tables\Columns\TextColumn::make('primaryGuest.full_name')->label('Purchaser')->default('—'),
                Tables\Columns\TextColumn::make('tour_date')->date(),
                Tables\Columns\TextColumn::make('timeSlot.start_time')->label('Time'),
                Tables\Columns\TextColumn::make('guests_count')
                    ->label('Guests')
                    ->formatStateUsing(fn ($record) => "{$record->guests_count} / {$record->items->sum('quantity')}"),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->url(fn (Booking $record) => route('filament.admin.resources.bookings.edit', $record)),
            ])
            ->emptyStateHeading('All bookings have complete guest details');
    }
}
