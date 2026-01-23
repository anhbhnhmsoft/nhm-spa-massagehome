<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Schemas\Components\Section;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GeneralStats extends BaseWidget
{
    protected function getStats(): array
    {
        $dashboardService = app(DashboardService::class);
        $result = $dashboardService->getGeneralStats();

        if (!$result->isSuccess()) {
            return [
                Stat::make('Error', 'Unable to load data')->color('danger'),
            ];
        }

        $data = $result->getData();

        return [
            Section::make(__('dashboard.general_stat.title'))
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    // Tổng doanh thu
                    Stat::make(__('dashboard.general_stat.total_income'), number_format($data['total_income'], 0, '.', ','))
                        ->description(__('dashboard.general_stat.total_income_desc'))
                        ->color('primary'),

                    // Chi phí vận hành
                    Stat::make(__('dashboard.general_stat.operation_cost'), number_format($data['operation_cost'], 0, '.', ','))
                        ->description(__('dashboard.general_stat.operation_cost_desc'))
                        ->color('danger'),

                    // Lợi nhuận
                    Stat::make(__('dashboard.general_stat.profit'), number_format($data['profit'], 0, '.', ','))
                        ->description(__('dashboard.general_stat.profit_desc'))
                        ->color('success'),

                    // Chi phí đại lý
                    Stat::make(__('dashboard.general_stat.agency_cost'), number_format($data['agency_cost'], 0, '.', ','))
                        ->description(__('dashboard.general_stat.agency_cost_desc'))
                        ->color('success'),

                    // Chi phí KTV
                    Stat::make(__('dashboard.general_stat.ktv_cost'), number_format($data['ktv_cost'], 0, '.', ','))
                        ->description(__('dashboard.general_stat.ktv_cost_desc'))
                        ->color('success'),

                    // Chi phí Affiliate
                    Stat::make(__('dashboard.general_stat.affiliate_cost'), number_format($data['affiliate_cost'], 0, '.', ','))
                        ->description(__('dashboard.general_stat.affiliate_cost_desc'))
                        ->color('success'),
                ])
        ];
    }
}
