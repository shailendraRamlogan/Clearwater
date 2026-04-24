<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;

class IncompleteBookings extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $view = 'filament.pages.incomplete-bookings';

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Guest Management';
    protected static ?int $navigationSort = 100;

    public function getBreadcrumbs(): array
    {
        return [
            static::getUrl() => 'Incomplete Bookings',
            'List',
        ];
    }

    public function getHeading(): string
    {
        return 'Incomplete Bookings';
    }

    public function getSubheading(): ?string
    {
        return 'Bookings where guest information is incomplete.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->withCount('guests')
                    ->with(['primaryGuest', 'timeSlot.boat', 'items'])
                    ->whereRaw('(SELECT COUNT(*) FROM booking_guests WHERE booking_guests.booking_id = bookings.id) < (SELECT COALESCE(SUM(quantity), 0) FROM booking_items WHERE booking_items.booking_id = bookings.id)')
                    ->orWhereRaw('(SELECT COUNT(*) FROM booking_guests WHERE booking_guests.booking_id = bookings.id AND booking_guests.is_primary = false AND (booking_guests.last_name = \'\' OR booking_guests.email = \'\')) > 0')
                    ->whereIn('status', ['pending'])
                    ->latest()
            )
            ->columns([
                TextColumn::make('booking_ref')->searchable()->sortable(),
                TextColumn::make('primaryGuest.full_name')->label('Purchaser')->default('—'),
                TextColumn::make('tour_date')->date()->sortable(),
                TextColumn::make('timeSlot.start_time')->label('Time'),
                TextColumn::make('timeSlot.boat.name')->label('Boat'),
                TextColumn::make('guests_count')
                    ->label('Guests')
                    ->badge()
                    ->color(fn ($record) => (1 + $record->guests()->where('is_primary', false)->where('last_name', '!=', '')->where('email', '!=', '')->count()) >= $record->items->sum('quantity') ? 'success' : 'warning')
                    ->formatStateUsing(fn ($record) => (1 + $record->guests()->where('is_primary', false)->where('last_name', '!=', '')->where('email', '!=', '')->count()) . ' / ' . $record->items->sum('quantity')),
                TextColumn::make('status')->badge(),
            ])
            ->actions([
                Tables\Actions\Action::make('manage')
                    ->url(fn ($record) => ManageGuests::getUrl(['record' => $record->id]))
                    ->icon('heroicon-o-pencil')
                    ->label('Manage Guests'),
            ])
            ->emptyStateHeading('No incomplete bookings')
            ->emptyStateDescription('All bookings have complete guest information.')
            ->paginated(10);
    }
}
