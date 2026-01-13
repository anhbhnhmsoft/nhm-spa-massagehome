<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GeneralStats extends BaseWidget
{
    protected function getStats(): array
    {
        $dashboardService = app(\App\Services\DashboardService::class);
        $result = $dashboardService->getGeneralStats();

        if (!$result->isSuccess()) {
            return [
                Stat::make('Error', 'Unable to load data')->color('danger'),
            ];
        }

        $data = $result->getData();

        return [
            Stat::make(__('admin.dashboard.widgets.general.order_volume'), $data['order_volume'])
                ->description(__('admin.dashboard.widgets.general.order_volume_desc'))
                ->color('primary'),

            Stat::make(__('admin.dashboard.widgets.general.sales'), number_format($data['sales']) . ' đ')
                ->description(__('admin.dashboard.widgets.general.sales_desc'))
                ->color('success'),

            Stat::make(__('admin.dashboard.widgets.general.net_sales'), number_format($data['net_sales']) . ' đ')
                ->description(__('admin.dashboard.widgets.general.net_sales_desc'))
                ->color('success'),

            Stat::make(__('admin.dashboard.widgets.general.commission'), number_format($data['commission_amount']) . ' đ')
                ->description(__('admin.dashboard.widgets.general.commission_desc'))
                ->color('warning'),

            Stat::make(__('admin.dashboard.widgets.general.coupon'), number_format($data['coupon_amount']) . ' đ')
                ->description(__('admin.dashboard.widgets.general.coupon_desc'))
                ->color('info'),
        ];
    }
}
