<?php

namespace App\Filament\Locador\Widgets;

use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PropertiesOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Imovéis', Property::where('user_id', auth()->id())->count())
                ->description('Total de imoveis')
                ->icon('heroicon-o-home'),
        ];
    }
}
