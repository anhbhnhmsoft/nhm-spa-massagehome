<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class ReviewRatingChart extends ChartWidget
{
    protected ?string $heading = null;

    public function getHeading(): string
    {
        return __('admin.dashboard.charts.review_ratings');
    }

    protected function getData(): array
    {
        $dashboardService = app(DashboardService::class);
        $result = $dashboardService->getReviewRatingChart();

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
                    'label' => 'Reviews',
                    'data' => $data['data'],
                    'backgroundColor' => [
                        'rgb(239, 68, 68)',
                        'rgb(249, 115, 22)',
                        'rgb(234, 179, 8)',
                        'rgb(168, 85, 247)',
                        'rgb(34, 197, 94)',
                    ],
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
