<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class BookingStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    /**
     * Only shown on Bookings list page via getHeaderWidgets(), not on the dashboard.
     */
    public static function isDiscovered(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $today = now()->toDateString();

        $bookingsToday = Booking::whereDate('created_at', $today)->count();
        $totalBookings = Booking::count();
        $revenueToday = Booking::whereDate('created_at', $today)
            ->where('status', '!=', 'cancelled')
            ->sum('total_price_cents');
        $totalRevenue = Booking::where('status', '!=', 'cancelled')
            ->sum('total_price_cents');

        return [
            ColoredStat::make('Bookings Today', number_format($bookingsToday))
                ->color('primary'),
            ColoredStat::make('Total Bookings', number_format($totalBookings))
                ->color('primary'),
            ColoredStat::make('Revenue Today', '$' . number_format($revenueToday / 100, 2))
                ->color('success'),
            ColoredStat::make('Total Revenue', '$' . number_format($totalRevenue / 100, 2))
                ->color('success'),
        ];
    }
}
