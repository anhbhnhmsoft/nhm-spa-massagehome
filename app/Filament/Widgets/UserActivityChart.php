<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class UserActivityChart extends ChartWidget
{
    protected ?string $heading = null;

    public function getHeading(): string
    {
        return __('admin.dashboard.charts.user_activity');
    }

    protected function getData(): array
    {
        $dashboardService = app(DashboardService::class);
        $result = $dashboardService->getUserActivityChart();

        if (!$result->isSuccess()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $data = $result->getData();

        return [
            'datasets' => [
                [
                    'label' => __('admin.dashboard.charts.new_users'),
                    'data' => $data['new_users'],
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => __('admin.dashboard.charts.active_users'),
                    'data' => $data['active_users'],
                    'borderColor' => 'rgb(249, 115, 22)',
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
