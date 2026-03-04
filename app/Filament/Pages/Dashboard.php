<?php

namespace App\Filament\Pages;

use App\Enums\DateRangeDashboard;
use App\Filament\Resources\DangerSupports\DangerSupportResource;
use App\Filament\Widgets\GeneralBookingStats;
use App\Filament\Widgets\GeneralStats;
use App\Filament\Widgets\TransactionChart;
use App\Filament\Widgets\UserStaticStats;
use App\Services\DashboardService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as PagesDashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends PagesDashboard
{
    use HasFiltersForm;


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

    public function filtersForm($schema)
    {
        return $schema
            ->components([
                Select::make('date_range')
                    ->label(__('admin.dashboard.filters.date_range'))
                    ->options(DateRangeDashboard::toOptions())
                    ->default(DateRangeDashboard::ALL->value)
                    ->selectablePlaceholder(false),
            ]);
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
