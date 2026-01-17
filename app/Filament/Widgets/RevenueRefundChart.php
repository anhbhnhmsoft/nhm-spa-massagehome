<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class RevenueRefundChart extends ChartWidget
{
    //    protected static ?string $heading = 'Doanh thu đơn hàng & Hoàn tiền (Revenue vs Refund)';
    protected int | string | array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    protected $listeners = ['dateRangeUpdated' => '$refresh'];

    public function getHeading(): string
    {
        return __('admin.dashboard.charts.revenue_refund_trend');
    }

    protected function getData(): array
    {
        $startDate = session('dashboard_start_date');
        $endDate = session('dashboard_end_date');

        $dashboardService = app(\App\Services\DashboardService::class);
        $result = $dashboardService->getRevenueRefundChartData($startDate, $endDate);

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
                    'label' => __('admin.dashboard.charts.revenue'),
                    'data' => $data['revenue'],
                    'borderColor' => '#10b981', // green-500
                    'fill' => true,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
                [
                    'label' => __('admin.dashboard.charts.refund'),
                    'data' => $data['refunds'],
                    'borderColor' => '#ef4444', // red-500
                    'fill' => true,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
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
