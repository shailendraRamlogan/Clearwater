<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentBookingsTable extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::with(['timeSlot.boat', 'primaryGuest'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('booking_ref')->searchable(),
                Tables\Columns\TextColumn::make('primaryGuest.first_name')
                    ->label('Guest')
                    ->default('-'),
                Tables\Columns\TextColumn::make('tour_date')->date(),
                Tables\Columns\TextColumn::make('timeSlot.boat.name')->label('Boat'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn($state) => match($state) {
                    'confirmed' => 'success',
                    'pending' => 'warning',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('total_price_cents')->money('usd', divideBy: 100)->label('Total'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn($record) => \App\Filament\Resources\BookingResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
