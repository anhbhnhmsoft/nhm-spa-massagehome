<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class TopServicesChart extends ChartWidget
{
    protected ?string $heading = null;

    public function getHeading(): string
    {
        return __('admin.dashboard.charts.top_services');
    }

    protected function getData(): array
    {
        $dashboardService = app(DashboardService::class);
        $result = $dashboardService->getTopServicesChart();

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
                    'label' => 'Bookings',
                    'data' => $data['counts'],
                    'backgroundColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
        ];
    }
}
