<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $dashboardService = app(DashboardService::class);
        $result = $dashboardService->getDashboardStats();

        if (!$result->isSuccess()) {
            return [];
        }

        $data = $result->getData();

        return [
            Stat::make(__('admin.dashboard.stats.revenue'), number_format($data['revenue']))
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            Stat::make(__('admin.dashboard.stats.booking_value'), number_format($data['booking_value']))
                ->color('success'),
            Stat::make(__('admin.dashboard.stats.new_bookings'), $data['new_bookings'])
                ->description('Tháng này')
                ->color('primary'),
            Stat::make(__('admin.dashboard.stats.new_users'), $data['new_users'])
                ->description('Tháng này')
                ->color('info'),
            Stat::make(__('admin.dashboard.stats.pending_profiles'), $data['pending_profiles'])
                ->color('warning'),
            Stat::make(__('admin.dashboard.stats.affiliate_commission'), number_format($data['affiliate_commission']))
        ];
    }
}
