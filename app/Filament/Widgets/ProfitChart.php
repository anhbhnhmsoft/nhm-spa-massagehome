<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class ProfitChart extends ChartWidget
{
    protected int | string | array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    protected $listeners = ['dateRangeUpdated' => '$refresh'];

    public function getHeading(): string
    {
        return __('admin.dashboard.charts.profit_trend');
    }

    protected function getData(): array
    {
        $startDate = session('dashboard_start_date');
        $endDate = session('dashboard_end_date');

        $dashboardService = app(\App\Services\DashboardService::class);
        $result = $dashboardService->getProfitChartData();

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
                    'label' => __('admin.dashboard.charts.profit'),
                    'data' => $data['data'],
                    'borderColor' => 'rgb(139, 92, 246)', // violet-500
                    'fill' => true,
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
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
