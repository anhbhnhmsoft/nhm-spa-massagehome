<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DangerSupportStats extends BaseWidget
{
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 0; 
    protected function getStats(): array
    {
        $dashboardService = app(DashboardService::class);
        $result = $dashboardService->getDangerSupportStats();

        if (!$result->isSuccess()) {
            return [
                Stat::make('Error', 'Unable to load data')->color('danger'),
            ];
        }
        $pendingCount = $result->getData()['pending_danger_supports'];

        return [
            Stat::make(__('dashboard.danger_support_stat.pending_danger_supports'), $pendingCount)
                ->description($pendingCount > 0 ? __('dashboard.danger_support_stat.pending_danger_supports_desc') : __('dashboard.danger_support_stat.no_pending_danger_supports'))
                ->descriptionIcon($pendingCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($pendingCount > 0 ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),
        ];
    }
}
