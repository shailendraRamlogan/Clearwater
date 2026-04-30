<?php

namespace App\Filament\Resources\BookingGuestResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingGuest;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ViewGuestBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    public string $guestEmail = '';
    public string $guestName = '';

    public function mount(): void
    {
        $segments = request()->segments();
        $this->guestEmail = end($segments);

        $guest = BookingGuest::where('email', $this->guestEmail)->first();
        $this->guestName = $guest
            ? "{$guest->first_name} {$guest->last_name}"
            : $this->guestEmail;

        parent::mount();
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $guestIds = BookingGuest::where('email', $this->guestEmail)
            ->pluck('booking_id')
            ->unique();

        return Booking::whereIn('id', $guestIds)
            ->with(['guests', 'items', 'timeSlot.boat']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('booking_ref')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tour_date')
                    ->date('M j, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('timeSlot.boat.name')
                    ->label('Boat'),
                Tables\Columns\TextColumn::make('timeSlot.start_time')
                    ->label('Time')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::createFromFormat('H:i:s', $state)->format('g:i A') : ''),
                Tables\Columns\TextColumn::make('adult_count')
                    ->label('Adults'),
                Tables\Columns\TextColumn::make('child_count')
                    ->label('Children'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($record) => '$' . number_format(($record->total_price_cents + $record->fees_cents) / 100, 2))
                    ->sortable(query: fn ($q, $dir) => $q->orderBy('total_price_cents', $dir)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'confirmed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn ($record) => "/bookings/{$record->id}/edit"),
            ])
            ->bulkActions([])
            ->defaultSort('tour_date', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to Guests')
                ->url('/booking-guests')
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return "Bookings — {$this->guestName}";
    }
}
