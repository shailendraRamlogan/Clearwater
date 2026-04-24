<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConfirmationRequired extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $view = 'filament.pages.confirmation-required';

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'Guest Management';
    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return ['Confirmation Required'];
    }

    public function getHeading(): string
    {
        return 'Confirmation Required';
    }

    public function getSubheading(): ?string
    {
        return 'Bookings flagged for duplicate guest data.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->with(['primaryGuest', 'timeSlot.boat', 'guests'])
                    ->where('needs_confirmation', true)
                    ->where('is_confirmed', false)
                    ->latest()
            )
            ->columns([
                TextColumn::make('booking_ref')->searchable()->sortable(),
                TextColumn::make('primaryGuest.full_name')->label('Purchaser')->default('—'),
                TextColumn::make('tour_date')->date()->sortable(),
                TextColumn::make('timeSlot.start_time')->label('Time'),
                TextColumn::make('timeSlot.boat.name')->label('Boat'),
                TextColumn::make('status')->badge(),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm Booking')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Booking $record) {
                        $record->update(['is_confirmed' => true, 'needs_confirmation' => false]);
                    }),
                Tables\Actions\Action::make('review')
                    ->url(fn ($record) => ManageGuests::getUrl(['record' => $record->id]))
                    ->icon('heroicon-o-pencil')
                    ->label('Review Guests'),
            ])
            ->emptyStateHeading('No bookings need confirmation')
            ->emptyStateDescription('All bookings have been reviewed and confirmed.')
            ->paginated(10);
    }
}
