<?php

namespace App\Filament\Widgets;

use App\Enums\DateRangeDashboard;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TransactionChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected int | string | array $columnSpan = 3;

    public function getHeading(): string
    {
        return __('admin.dashboard.charts.transaction_dashboard');
    }

    protected function getData(): array
    {
        $dateRange = $this->pageFilters['date_range'] ? DateRangeDashboard::tryFrom($this->pageFilters['date_range']) : DateRangeDashboard::ALL;

        $dashboardService = app(\App\Services\DashboardService::class);
        $result = $dashboardService->getTransactionChartData($dateRange);
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
                    'label' => __('admin.dashboard.charts.income'),
                    'data' => $data['income'],
                    'borderColor' => '#10b981', // green-500
                    'fill' => true,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
//                [
//                    'label' => __('admin.dashboard.charts.refund'),
//                    'data' => $data['refunds'],
//                    'borderColor' => '#ef4444', // red-500
//                    'fill' => true,
//                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
//                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
