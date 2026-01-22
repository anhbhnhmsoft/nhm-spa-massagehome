<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as PagesDashboard;

class Dashboard extends PagesDashboard
{

    public static function getNavigationLabel(): string
    {
        return __('dashboard.navigation_label');
    }

public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\GeneralStats::class,
            \App\Filament\Widgets\OperationCostStats::class,
            \App\Filament\Widgets\RevenueRefundChart::class,
            \App\Filament\Widgets\ProfitChart::class,
            \App\Filament\Widgets\TechnicianStatusStats::class,
            \App\Filament\Widgets\TechnicianLeaderboard::class,
        ];
    }
}
