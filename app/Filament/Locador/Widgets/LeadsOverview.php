<?php

namespace App\Filament\Locador\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeadsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // TODO: Replace with actual lead count when Lead model is implemented
        // Query should be: Lead::where('user_id', auth()->id())->count()
        return [
            Stat::make('Leads', 0)
                ->description('Totais de leads')
                ->icon('heroicon-o-user-group'),
        ];
    }
}
