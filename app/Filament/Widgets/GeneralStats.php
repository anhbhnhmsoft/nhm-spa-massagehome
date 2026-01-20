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

            Stat::make(__('dashboard.general_stat.booking_confirmed'), $data['booking_confirmed'])
                ->description(__('dashboard.general_stat.booking_confirmed_desc'))
                ->color('success'),

            Stat::make(__('dashboard.operation_cost.active_order_count'), $data['active_order_count'])
                ->color('primary'),

            Stat::make(__('dashboard.general_stat.canceled_booking'), $data['canceled_booking'])
                ->color('danger'),

            Stat::make(__('dashboard.general_stat.payment_failed'), $data['payment_failed'])
                ->description(__('dashboard.general_stat.payment_failed_desc'))
                ->color('danger'),
        ];
    }
}
