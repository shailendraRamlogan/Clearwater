<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class IncompleteBookingsWidget extends BaseWidget
{
    protected static ?string $heading = 'Incomplete Bookings';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Booking::query()
            ->withCount('guests')
            ->with(['primaryGuest', 'timeSlot.boat', 'items'])
            ->whereRaw('(SELECT COUNT(*) FROM booking_guests WHERE booking_guests.booking_id = bookings.id) < (SELECT COALESCE(SUM(quantity), 0) FROM booking_items WHERE booking_items.booking_id = bookings.id)')
            ->whereIn('status', ['pending', 'confirmed'])
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('booking_ref')->searchable(),
            TextColumn::make('primaryGuest.full_name')->label('Purchaser')->default('—'),
            TextColumn::make('tour_date')->date(),
            TextColumn::make('timeSlot.start_time')->label('Time'),
            TextColumn::make('guests_count')
                ->label('Guests')
                ->formatStateUsing(fn ($record) => "{$record->guests_count} / {$record->items->sum('quantity')}"),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('edit')
                ->url(fn ($record) => "/bookings/{$record->id}/edit")
                ->icon('heroicon-o-pencil')
                ->label('Edit'),
        ];
    }
}
