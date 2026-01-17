<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GeneralStats extends BaseWidget
{
    protected ?string $pollingInterval = null;
    protected $listeners = ['dateRangeUpdated' => '$refresh'];

    protected function getStats(): array
    {
        $startDate = session('dashboard_start_date');
        $endDate = session('dashboard_end_date');

        $dashboardService = app(\App\Services\DashboardService::class);
        $result = $dashboardService->getGeneralStats($startDate, $endDate);

        if (!$result->isSuccess()) {
            return [
                Stat::make('Error', 'Unable to load data')->color('danger'),
            ];
        }

        $data = $result->getData();

        return [
            Stat::make(__('dashboard.general_stat.total_booking'), $data['total_booking'])
                ->description(__('dashboard.general_stat.total_booking_desc'))
                ->color('primary'),

            Stat::make(__('dashboard.general_stat.completed_booking'), $data['completed_booking'])
                ->color('success'),

            Stat::make(__('dashboard.general_stat.canceled_booking'), $data['canceled_booking'])
                ->color('danger'),

            Stat::make(__('dashboard.general_stat.gross_revenue'), number_format($data['gross_revenue']) . ' đ')
                ->description(__('dashboard.general_stat.gross_revenue_desc'))
                ->color('success'),

            Stat::make(__('dashboard.general_stat.ktv_cost'), number_format($data['ktv_cost']) . ' đ')
                ->description(__('dashboard.general_stat.ktv_cost_desc'))
                ->color('warning'),

            Stat::make(__('dashboard.general_stat.net_profit'), number_format($data['net_profit']) . ' đ')
                ->description(__('dashboard.general_stat.net_profit_desc'))
                ->color('info'),
        ];
    }
}
