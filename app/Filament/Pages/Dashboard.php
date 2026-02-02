<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\GeneralBookingStats;
use App\Filament\Widgets\GeneralStats;
use App\Filament\Widgets\TransactionChart;
use App\Filament\Widgets\UserStaticStats;
use Filament\Pages\Dashboard as PagesDashboard;

class Dashboard extends PagesDashboard
{
    protected string $pollingInterval = '5m';

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
            GeneralStats::class,
            GeneralBookingStats::class,
            UserStaticStats::class,
            TransactionChart::class,
        ];
    }
}
