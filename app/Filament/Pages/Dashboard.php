<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as PagesDashboard;

class Dashboard extends PagesDashboard
{
    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\DashboardStatsOverview::class,
            \App\Filament\Widgets\RevenueChart::class,
            \App\Filament\Widgets\UserActivityChart::class,
            \App\Filament\Widgets\UserRoleChart::class,
            \App\Filament\Widgets\BookingStatusChart::class,
            \App\Filament\Widgets\TopServicesChart::class,
            \App\Filament\Widgets\ReviewRatingChart::class,
        ];
    }
}
