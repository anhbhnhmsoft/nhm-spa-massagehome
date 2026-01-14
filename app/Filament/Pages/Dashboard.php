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
            \App\Filament\Widgets\OperationCostStats::class,
//            \App\Filament\Widgets\AddOnServiceStats::class,
            \App\Filament\Widgets\GeneralStats::class,
            \App\Filament\Widgets\RevenueRefundChart::class,
            \App\Filament\Widgets\ProfitChart::class,
            \App\Filament\Widgets\TechnicianStatusStats::class,
            \App\Filament\Widgets\TechnicianLeaderboard::class,
            // \App\Filament\Widgets\DashboardStatsOverview::class,
            // \App\Filament\Widgets\RevenueChart::class,
            // \App\Filament\Widgets\UserActivityChart::class,
            // \App\Filament\Widgets\UserRoleChart::class,
            // \App\Filament\Widgets\BookingStatusChart::class,
            // \App\Filament\Widgets\TopServicesChart::class,
            // \App\Filament\Widgets\ReviewRatingChart::class,
        ];
    }
}
