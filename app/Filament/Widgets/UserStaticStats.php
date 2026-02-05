<?php

namespace App\Filament\Widgets;

use App\Enums\ReviewApplicationStatus;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\AgencyResource;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
use App\Services\DashboardService;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStaticStats extends BaseWidget
{

    protected int|string|array $columnSpan = 3;

    protected function getStats(): array
    {
        $dashboardService = app(DashboardService::class);

        // Get Revenue stats from GeneralStats
        $result = $dashboardService->getGeneralUserStats();
        if (!$result->isSuccess()) {
            return [
                Stat::make('Error', 'Unable to load data')->color('danger'),
            ];
        }
        $data = $result->getData();

        return [
            Section::make(__('dashboard.user_static_stats.title'))
                ->columnSpanFull()
                ->compact()
                ->schema([
                    Grid::make()
                        ->columns(2)
                        ->schema([
                            Grid::make()
                                ->columns(1)
                                ->schema([
                                    Stat::make(__('dashboard.user_static_stats.total_ktv'), $data['total_ktv'])
                                        ->icon(Heroicon::User)
                                        ->url(KTVResource::getUrl('index'))
                                        ->chart([7, 2, 10, 3, 15, 4, 17])
                                        ->color('primary'),
                                    Stat::make(__('dashboard.user_static_stats.pending_ktv'), $data['pending_ktv'])
                                        ->icon(Heroicon::UserPlus)
                                        ->url(KTVResource::getUrl('index',[
                                            'filters' => [
                                                'review_status' => [
                                                    'value' => ReviewApplicationStatus::PENDING->value,
                                                ],
                                            ]
                                        ]))
                                        ->chart([7, 2, 10, 3, 15, 4, 17])
                                        ->color('warning'),
                                ]),
                            Grid::make()
                                ->columns(1)
                                ->schema([
                                    Stat::make(__('dashboard.user_static_stats.total_agency'), $data['total_agency'])
                                        ->icon(Heroicon::UserGroup)
                                        ->url(AgencyResource::getUrl('index'))
                                        ->chart([7, 2, 10, 3, 15, 4, 17])
                                        ->color('info'),
                                    Stat::make(__('dashboard.user_static_stats.pending_agency'), $data['pending_agency'])
                                        ->icon(Heroicon::OutlinedUserGroup)
                                        ->url(AgencyResource::getUrl('index',[
                                            'filters' => [
                                                'review_status' => [
                                                    'value' => ReviewApplicationStatus::PENDING->value,
                                                ],
                                            ]
                                        ]))
                                        ->chart([7, 2, 10, 3, 15, 4, 17])
                                        ->color('warning'),
                                ])
                        ]),
                    Grid::make()
                        ->columns(3)
                        ->schema([
                            Stat::make(__('dashboard.user_static_stats.total_customer'), $data['total_customer'])
                                ->icon(Heroicon::UserGroup)
                                ->color('primary'),
                            Stat::make(__('dashboard.user_static_stats.withdraw_requests'), $data['withdraw_requests'])
                                ->icon(Heroicon::Banknotes)
                                ->color('info'),
                            Stat::make(__('dashboard.user_static_stats.review'), $data['review_count'])
                                ->icon(Heroicon::ChatBubbleOvalLeft)
                                ->color('warning'),
                        ]),
                ])
        ];
    }
}
