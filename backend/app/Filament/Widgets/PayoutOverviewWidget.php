<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PayoutOverviewWidget extends BaseWidget
{
    protected static ?int $sort = -100;

    public static function isDiscovered(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $availableCents = Booking::where('status', 'confirmed')
            ->whereHas('payments', function ($q) {
                $q->where('status', 'succeeded')
                  ->where('paid_out', false);
            })
            ->selectRaw('COALESCE(SUM(total_price_cents), 0) as total')
            ->value('total');

        return [
            Stat::make('Available for Payout', '$' . number_format($availableCents / 100, 2))
                ->color('primary')
                ->icon('heroicon-o-currency-dollar'),
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'super_admin']);
    }
}
