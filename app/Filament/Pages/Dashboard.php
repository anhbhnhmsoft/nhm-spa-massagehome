<?php

namespace App\Filament\Pages;

use App\Enums\DangerSupportStatus;
use App\Filament\Resources\DangerSupports\DangerSupportResource;
use App\Filament\Widgets\GeneralBookingStats;
use App\Filament\Widgets\GeneralStats;
use App\Filament\Widgets\TransactionChart;
use App\Filament\Widgets\UserStaticStats;
use App\Models\DangerSupport;
use App\Services\DashboardService;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as PagesDashboard;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Dashboard extends PagesDashboard
{
    public function getColumns(): int
    {
        return 6;
    }
    protected string $pollingInterval = '5m';

    public static function getNavigationLabel(): string
    {
        return __('dashboard.navigation_label');
    }

    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    protected function getHeaderActions(): array
    {
        $dashboardService = app(DashboardService::class);
        $result = $dashboardService->getDangerSupportStats();
        if (!$result->isSuccess()) {
            return [];
        }

        $sosCount = $result->getData()['pending_danger_supports'] ?? 0;


        return [
            Action::make('sos_count')
                ->label("SOS: {$sosCount}")
                ->color($sosCount > 0 ? 'danger' : 'gray')
                ->badge($sosCount)
                ->icon('heroicon-m-exclamation-triangle')
                ->url(DangerSupportResource::getUrl('index'))
                ->extraAttributes([
                    'class' => $sosCount > 0 ? 'animate-pulse' : '', // Nhấp nháy nếu có SOS
                ]),
        ];
    }

    public function getWidgets(): array
    {
        return [
            GeneralStats::class,
            GeneralBookingStats::class,
            UserStaticStats::class,
            TransactionChart::class,
        ];
    }
}
