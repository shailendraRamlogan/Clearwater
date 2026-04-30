<?php

namespace App\Filament\Widgets\Payout;

use App\Models\Booking;
use App\Models\Payout;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PayoutStatsWidget extends BaseWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        // Total net revenue from all confirmed bookings with succeeded payments
        $totalNetCents = Booking::where('status', 'confirmed')
            ->whereHas('payments', fn ($q) => $q->where('status', 'succeeded'))
            ->selectRaw('COALESCE(SUM(total_price_cents), 0) as total')
            ->value('total');

        // Processing = sum of pending payout amounts
        $processingCents = Payout::where('status', 'pending')
            ->selectRaw('COALESCE(SUM(amount_cents), 0) as total')
            ->value('total');

        // Paid to date = sum of confirmed payout amounts
        $paidCents = Payout::where('status', 'confirmed')
            ->selectRaw('COALESCE(SUM(amount_cents), 0) as total')
            ->value('total');

        // Available = total net - what's being processed - what's been paid
        $availableCents = $totalNetCents - $processingCents - $paidCents;

        return [
            Stat::make('Available for Payout', '$' . number_format(max(0, $availableCents) / 100, 2))
                ->description('Net Revenue')
                ->color('primary')
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('Processing', '$' . number_format($processingCents / 100, 2))
                ->description('Pending payout requests')
                ->color('warning')
                ->icon('heroicon-o-clock'),
            Stat::make('Paid to Date', '$' . number_format($paidCents / 100, 2))
                ->description('Confirmed payouts')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'super_admin']);
    }
}
