<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class PaymentStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    /**
     * Only shown on Payments list page via getHeaderWidgets(), not on the dashboard.
     */
    public static function isDiscovered(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $today = now()->toDateString();

        $todayStats = Payment::whereDate('payments.created_at', $today)
            ->where('payments.status', 'succeeded')
            ->leftJoin('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->selectRaw('
                COALESCE(SUM(payments.amount_cents), 0) as revenue,
                COALESCE(SUM(bookings.fees_cents), 0) as fees,
                COALESCE(SUM(bookings.total_price_cents), 0) as payout
            ')
            ->first();

        $allTimeStats = Payment::where('payments.status', 'succeeded')
            ->leftJoin('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->selectRaw('
                COALESCE(SUM(payments.amount_cents), 0) as revenue,
                COALESCE(SUM(bookings.fees_cents), 0) as fees,
                COALESCE(SUM(bookings.total_price_cents), 0) as payout
            ')
            ->first();

        return [
            ColoredStat::make('Revenue Today', '$' . number_format($todayStats->revenue / 100, 2))
                ->color('success'),
            ColoredStat::make('Fees Today', '$' . number_format($todayStats->fees / 100, 2))
                ->color('warning'),
            ColoredStat::make('Revenue Today (Net)', '$' . number_format($todayStats->payout / 100, 2))
                ->color('primary'),
            ColoredStat::make('Total Revenue', '$' . number_format($allTimeStats->revenue / 100, 2))
                ->color('success'),
            ColoredStat::make('Total Fees', '$' . number_format($allTimeStats->fees / 100, 2))
                ->color('warning'),
            ColoredStat::make('Total Revenue (Net)', '$' . number_format($allTimeStats->payout / 100, 2))
                ->color('primary'),
        ];
    }
}
