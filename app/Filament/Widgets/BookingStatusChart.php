<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class BookingStatusChart extends ChartWidget
{
    protected ?string $heading = null;

    protected int | string | array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('admin.dashboard.charts.booking_status');
    }

    protected function getData(): array
    {
        $dashboardService = app(DashboardService::class);
        $result = $dashboardService->getBookingStatusChart();

        if (!$result->isSuccess()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $data = $result->getData();

        return [
            'datasets' => $data['datasets'],
            'labels' => $data['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
