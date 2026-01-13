<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationCostStats extends BaseWidget
{
    protected function getStats(): array
    {
        $dashboardService = app(\App\Services\DashboardService::class);
        $result = $dashboardService->getOperationCostStats();

        if (!$result->isSuccess()) {
            // Fallback or error handling
            return [
                Stat::make('Error', 'Unable to load data')->color('danger'),
            ];
        }

        $data = $result->getData();

        return [
            Stat::make(__('admin.dashboard.widgets.operation_cost.label'), number_format($data['operation_cost']) . ' đ')
                ->description(__('admin.dashboard.widgets.operation_cost.desc'))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make(__('admin.dashboard.widgets.operation_cost.primary_order_count'), $data['primary_order_count'])
                ->description(__('admin.dashboard.widgets.operation_cost.primary_order_desc'))
                ->color('primary'),

            Stat::make(__('admin.dashboard.widgets.operation_cost.primary_service_value'), number_format($data['primary_service_value']) . ' đ')
                ->description(__('admin.dashboard.widgets.operation_cost.primary_service_desc'))
                ->color('success'),

            Stat::make(__('admin.dashboard.widgets.operation_cost.canceled_orders'), $data['canceled_orders'])
                ->description(__('admin.dashboard.widgets.operation_cost.canceled_orders_desc'))
                ->color('warning'),

            Stat::make(__('admin.dashboard.widgets.operation_cost.refund_amount'), number_format($data['refund_amount']) . ' đ')
                ->description(__('admin.dashboard.widgets.operation_cost.refund_amount_desc'))
                ->color('danger'),
        ];
    }
}
