<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use Filament\Pages\Page;

class ManageGuests extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Guest Management';
    protected static ?int $navigationSort = 99;
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-guests';

    public Booking $booking;

    public function mount(string $record): void
    {
        $this->booking = Booking::with(['guests', 'items', 'timeSlot.boat', 'primaryGuest'])
            ->findOrFail($record);
    }

    public static function getRoutePath(): string
    {
        return '/guests/{record}';
    }

    public function getHeading(): string
    {
        return 'Manage Guests';
    }

    public function getSubheading(): ?string
    {
        return $this->booking->booking_ref
            . ' — '
            . ($this->booking->tour_date?->format('F j, Y'))
            . ' — '
            . ($this->booking->timeSlot?->boat?->name ?? 'Unknown');
    }

    public function getBreadcrumbs(): array
    {
        return [
            IncompleteBookings::getUrl() => 'Incomplete Bookings',
            'Manage Guests',
        ];
    }
}
