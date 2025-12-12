<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = null;

    protected int | string | array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('admin.dashboard.charts.revenue_trend');
    }

    protected function getData(): array
    {
        $dashboardService = app(DashboardService::class);
        $result = $dashboardService->getRevenueChart();

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
                    'label' => __('admin.dashboard.charts.revenue_deposit'),
                    'data' => $data['deposits'],
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => __('admin.dashboard.charts.revenue_booking'),
                    'data' => $data['bookings'],
                    'borderColor' => 'rgb(59, 130, 246)',
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
