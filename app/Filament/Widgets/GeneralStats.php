<?php

namespace App\Filament\Widgets;

use App\Core\Helper;
use App\Services\DashboardService;
use Filament\Schemas\Components\Grid;
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

        $systemInout = $data['system_inout'];

        $revenue = $data['revenue'];

        return [
            // Chi phí vân hành
            Grid::make()
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    // Tiền in out hệ thống
                    Section::make(__('dashboard.general_stat.title_income_system'))
                        ->columnSpan(1)
                        ->schema([
                            // Tổng tiền nạp vào hệ thống
                            Stat::make(__('dashboard.general_stat.total_income'), Helper::formatPrice($systemInout['total_income']))
                                ->color('primary'),
                            Stat::make(__('dashboard.general_stat.total_outcome'), Helper::formatPrice($systemInout['total_outcome']))
                                ->color('danger'),
                        ]),

                    // Doanh số hệ thống
                    Section::make(__('dashboard.general_stat.title_revenue_system'))
                        ->columnSpan(3)
                        ->columns(3)
                        ->schema([
                            Stat::make(__('dashboard.general_stat.total_revenue'), Helper::formatPrice($revenue['total_revenue']))
                                ->color('primary'),
                            Stat::make(__('dashboard.general_stat.operation_cost'), Helper::formatPrice($revenue['operation_cost']))
                                ->color('danger'),
                            Stat::make(__('dashboard.general_stat.profit'), Helper::formatPrice($revenue['profit']))
                                ->color('success'),
                            Grid::make()
                                ->columns(4)
                                ->columnSpanFull()
                                ->schema([
                                    Stat::make(__('dashboard.general_stat.agency_cost'), Helper::formatPrice($revenue['agency_cost']))
                                        ->color('danger'),
                                    Stat::make(__('dashboard.general_stat.ktv_cost'), Helper::formatPrice($revenue['technical_cost']))
                                        ->color('danger'),
                                    Stat::make(__('dashboard.general_stat.customer_cost'), Helper::formatPrice($revenue['customer_cost']))
                                        ->color('danger'),
                                    Stat::make(__('dashboard.general_stat.transportation_cost'), Helper::formatPrice($revenue['transportation_cost']))
                                        ->color('danger'),
                                ])

                        ]),


                ])
//            Section::make(__('dashboard.general_stat.title'))
//                ->columnSpanFull()
//                ->columns(4)
//                ->schema([

//
//                    // Chi phí vận hành
//                    Stat::make(__('dashboard.general_stat.operation_cost'), number_format($data['operation_cost'], 0, '.', ','))
//                        ->description(__('dashboard.general_stat.operation_cost_desc'))
//                        ->color('danger'),
//
//                    // Lợi nhuận
//                    Stat::make(__('dashboard.general_stat.profit'), number_format($data['profit'], 0, '.', ','))
//                        ->description(__('dashboard.general_stat.profit_desc'))
//                        ->color('success'),
//
//                    // Chi phí đại lý
//                    Stat::make(__('dashboard.general_stat.agency_cost'), number_format($data['agency_cost'], 0, '.', ','))
//                        ->description(__('dashboard.general_stat.agency_cost_desc'))
//                        ->color('success'),
//
//                    // Chi phí KTV
//                    Stat::make(__('dashboard.general_stat.ktv_cost'), number_format($data['ktv_cost'], 0, '.', ','))
//                        ->description(__('dashboard.general_stat.ktv_cost_desc'))
//                        ->color('success'),
//
//                    // Chi phí Affiliate
//                    Stat::make(__('dashboard.general_stat.affiliate_cost'), number_format($data['affiliate_cost'], 0, '.', ','))
//                        ->description(__('dashboard.general_stat.affiliate_cost_desc'))
//                        ->color('success'),
//                ])
        ];
    }
}
