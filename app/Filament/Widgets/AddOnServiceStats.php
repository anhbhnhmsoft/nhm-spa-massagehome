<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AddOnServiceStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(__('admin.dashboard.widgets.add_on.volume'), '0')
                ->description(__('admin.dashboard.widgets.add_on.volume_desc'))
                ->color('gray'),

            Stat::make(__('admin.dashboard.widgets.add_on.revenue'), '0 đ')
                ->description(__('admin.dashboard.widgets.add_on.revenue_desc'))
                ->color('gray'),

            Stat::make(__('admin.dashboard.widgets.add_on.refund_volume'), '0')
                ->description(__('admin.dashboard.widgets.add_on.refund_volume_desc'))
                ->color('gray'),

            Stat::make(__('admin.dashboard.widgets.add_on.refund_amount'), '0 đ')
                ->description(__('admin.dashboard.widgets.add_on.refund_amount_desc'))
                ->color('gray'),
        ];
    }
}
