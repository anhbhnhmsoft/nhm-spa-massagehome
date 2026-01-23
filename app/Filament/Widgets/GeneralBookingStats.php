<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Schemas\Components\Section;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GeneralBookingStats extends BaseWidget
{
    protected function getStats(): array
    {
        $dashboardService = app(DashboardService::class);

        // Get Revenue stats from GeneralStats
        $result = $dashboardService->getGeneralBookingStats();
        if (!$result->isSuccess()) {
            return [
                Stat::make('Error', 'Unable to load data')->color('danger'),
            ];
        }
        $data = $result->getData();

        return [
            Section::make(__('dashboard.general_booking_stat.title'))
                ->columnSpanFull()
                ->columns(5)
                ->schema([
                    Stat::make(__('dashboard.general_booking_stat.total_booking'), $data['total_booking'])
                        ->description(__('dashboard.general_booking_stat.total_booking_desc'))
                        ->color('success'),
                    Stat::make(__('dashboard.general_booking_stat.pending_booking'), $data['pending_booking'])
                        ->description(__('dashboard.general_booking_stat.pending_booking_desc'))
                        ->color('warning'),
                    Stat::make(__('dashboard.general_booking_stat.ongoing_booking'), $data['ongoing_booking'])
                        ->description(__('dashboard.general_booking_stat.ongoing_booking_desc'))
                        ->color('info'),
                    Stat::make(__('dashboard.general_booking_stat.completed_booking'), $data['completed_booking'])
                        ->description(__('dashboard.general_booking_stat.completed_booking_desc'))
                        ->color('success'),
                    Stat::make(__('dashboard.general_booking_stat.canceled_booking'), $data['canceled_booking'])
                        ->description(__('dashboard.general_booking_stat.canceled_booking_desc'))
                        ->color('danger'),
                ])
        ];
    }
}
