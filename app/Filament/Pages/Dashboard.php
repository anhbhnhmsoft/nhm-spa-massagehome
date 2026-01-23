<?php

namespace App\Filament\Pages;

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
            \App\Filament\Widgets\GeneralStats::class,
            \App\Filament\Widgets\GeneralBookingStats::class,
            \App\Filament\Widgets\UserStaticStats::class,
            \App\Filament\Widgets\TransactionChart::class,
        ];
    }
}
