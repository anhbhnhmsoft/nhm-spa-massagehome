<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationCostStats extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected $listeners = ['dateRangeUpdated' => '$refresh'];

    protected function getStats(): array
    {
        $startDate = session('dashboard_start_date');
        $endDate = session('dashboard_end_date');

        $dashboardService = app(\App\Services\DashboardService::class);

        // Get Revenue stats from GeneralStats
        $generalResult = $dashboardService->getGeneralStats($startDate, $endDate);
        if (!$generalResult->isSuccess()) {
            return [Stat::make('Error', 'Unable to load data')->color('danger')];
        }
        $generalData = $generalResult->getData();

        // Get Cost stats from OperationCostStats
        $operationResult = $dashboardService->getOperationCostStats($startDate, $endDate);
        if (!$operationResult->isSuccess()) {
            return [
                Stat::make('Error', 'Unable to load data')->color('danger'),
            ];
        }
        $operationData = $operationResult->getData();

        return [
            Stat::make(__('dashboard.general_stat.gross_revenue'), number_format($generalData['gross_revenue']) . ' đ')
                ->description(__('dashboard.general_stat.gross_revenue_desc'))
                ->color('success'),

            Stat::make(__('dashboard.general_stat.ktv_cost'), number_format($generalData['ktv_cost']) . ' đ')
                ->description(__('dashboard.general_stat.ktv_cost_desc'))
                ->color('warning'),

            Stat::make(__('dashboard.general_stat.net_profit'), number_format($generalData['net_profit']) . ' đ')
                ->description(__('dashboard.general_stat.net_profit_desc'))
                ->color('info'),

            Stat::make(__('dashboard.operation_cost.refund_amount'), number_format($operationData['refund_amount']) . ' đ')
                ->color('danger'),

            Stat::make(__('dashboard.operation_cost.fee_amount'), number_format($operationData['fee_amount_for_affiliate']) . ' đ')
                ->color('danger'),

            Stat::make(__('dashboard.operation_cost.fee_amount_from_ktv_for_customer'), number_format($operationData['fee_amount_for_ktv_for_customer']) . ' đ')
                ->color('danger'),

            Stat::make(__('dashboard.operation_cost.deposit_amount'), number_format($operationData['deposit_amount']) . ' đ')
                ->color('green'),
        ];
    }
}
