<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use App\Enums\UserRole;
use Filament\Widgets\ChartWidget;

class UserRoleChart extends ChartWidget
{
    protected ?string $heading = null;

    public function getHeading(): string
    {
        return __('admin.dashboard.charts.user_roles');
    }

    protected function getData(): array
    {
        $dashboardService = app(DashboardService::class);
        $result = $dashboardService->getUserRoleChart();

        if (!$result->isSuccess()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $data = $result->getData();
        $labels = collect($data['roles'])->map(fn($role) => UserRole::tryFrom($role)?->label() ?? $role);

        return [
            'datasets' => [
                [
                    'label' => 'Users',
                    'data' => $data['counts'],
                    'backgroundColor' => [
                        'rgb(54, 162, 235)',
                        'rgb(255, 99, 132)',
                        'rgb(255, 205, 86)',
                        'rgb(75, 192, 192)',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
