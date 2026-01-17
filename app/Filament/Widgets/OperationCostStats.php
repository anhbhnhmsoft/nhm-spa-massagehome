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
        $result = $dashboardService->getOperationCostStats($startDate, $endDate);

        if (!$result->isSuccess()) {
            // Fallback or error handling
            return [
                Stat::make('Error', 'Unable to load data')->color('danger'),
            ];
        }

        $data = $result->getData();

        return [
            Stat::make(__('dashboard.operation_cost.active_order_count'), $data['active_order_count'])
                ->color('primary'),
            Stat::make(__('dashboard.operation_cost.refund_amount'), number_format($data['refund_amount']) . ' đ')
                ->color('danger'),
            Stat::make(__('dashboard.operation_cost.fee_amount'), number_format($data['fee_amount']) . ' đ')
                ->color('danger'),
            Stat::make(__('dashboard.operation_cost.deposit_amount'),number_format($data['deposit_amount']) . ' đ')
                ->color('green'),
        ];
    }
}

