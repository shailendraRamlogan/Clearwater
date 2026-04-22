<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\BookingGuest;
use App\Models\TimeSlot;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $today = now()->toDateString();

        $bookingsToday = Booking::whereDate('created_at', $today)->count();
        $revenueToday = Payment::where('status', 'succeeded')
            ->whereDate('created_at', $today)
            ->sum('amount_cents') / 100;
        $totalGuests = BookingGuest::count();
        $totalBookings = Booking::where('status', 'confirmed')
            ->where('tour_date', '>=', $today)
            ->count();
        $totalCapacity = TimeSlot::sum('max_capacity');
        $occupancyRate = $totalCapacity > 0 ? round(($totalBookings / $totalCapacity) * 100, 1) : 0;

        return [
            Stat::make('Bookings Today', $bookingsToday)
                ->description('New bookings')
                ->icon('heroicon-o-calendar-days'),
            Stat::make('Revenue Today', '$' . number_format($revenueToday, 2))
                ->description('Successful payments')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Total Guests', $totalGuests)
                ->description('All-time registered guests')
                ->icon('heroicon-o-users'),
            Stat::make('Occupancy Rate', $occupancyRate . '%')
                ->description('Upcoming confirmed bookings')
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}
