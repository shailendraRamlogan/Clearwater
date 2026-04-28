<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\View\View;

class ColoredStat extends Stat
{
    public function render(): View
    {
        return view('filament.widgets.colored-stats-stat', $this->data());
    }
}
