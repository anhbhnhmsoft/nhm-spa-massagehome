<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TechnicianStatusStats extends BaseWidget
{
    protected function getStats(): array
    {
        $dashboardService = app(\App\Services\DashboardService::class);
        $result = $dashboardService->getTechnicianStatusStats();

        if (!$result->isSuccess()) {
            return [
                Stat::make('Error', 'Unable to load data')->color('danger'),
            ];
        }

        $data = $result->getData();

        return [
            Stat::make(__('admin.dashboard.widgets.technician_status.total'), $data['total_ktv'])
                ->description(__('admin.dashboard.widgets.technician_status.total_desc'))
                ->color('primary'),

            Stat::make(__('admin.dashboard.widgets.technician_status.online'), $data['online_ktv_count'])
                ->description(__('admin.dashboard.widgets.technician_status.online_desc'))
                ->color('success'),

            Stat::make(__('admin.dashboard.widgets.technician_status.resting'), $data['resting_ktv_count'])
                ->description(__('admin.dashboard.widgets.technician_status.resting_desc'))
                ->color('gray'),

            Stat::make(__('admin.dashboard.widgets.technician_status.working'), $data['working_ktv_count'])
                ->description(__('admin.dashboard.widgets.technician_status.working_desc'))
                ->color('warning'),
        ];
    }
}
