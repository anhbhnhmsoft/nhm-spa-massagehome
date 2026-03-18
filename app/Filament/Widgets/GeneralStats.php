<?php

namespace App\Filament\Widgets;

use App\Core\Helper;
use App\Enums\Admin\AdminGate;
use App\Enums\Admin\AdminRole;
use App\Enums\DateRangeDashboard;
use App\Services\DashboardService;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

class GeneralStats extends BaseWidget
{
    use InteractsWithPageFilters;

    public static function canView(): bool
    {
        return Gate::allows(AdminGate::ALLOW_ACCOUNTANT);
    }
    protected function getStats(): array
    {
        $dateRange = $this->pageFilters['date_range'] ? DateRangeDashboard::tryFrom($this->pageFilters['date_range']) : DateRangeDashboard::ALL;

        $dashboardService = app(DashboardService::class);
        $result = $dashboardService->getGeneralStats($dateRange);

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
                        ->compact()
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
                        ->compact()
                        ->columnSpan(3)
                        ->columns(3)
                        ->schema([
                            Stat::make(__('dashboard.general_stat.total_revenue'), Helper::formatPrice($revenue['total_revenue']))
                                ->color('primary'),
                            Stat::make(__('dashboard.general_stat.operation_cost'), Helper::formatPrice($revenue['operation_cost']))
                                ->color('danger'),
                            Stat::make(__('dashboard.general_stat.profit'), Helper::formatPrice($revenue['profit']))
                                ->color('success'),
                            Section::make(__('dashboard.general_stat.title_operation_system'))
                                ->columns(3)
                                ->columnSpanFull()
                                ->compact()
                                ->schema([
                                    // Chi phí đại lý
                                    Stat::make(__('dashboard.general_stat.agency_cost'), Helper::formatPrice($revenue['agency_cost'])),
                                    // Chi phí khách hàng
                                    Stat::make(__('dashboard.general_stat.ktv_cost'), Helper::formatPrice($revenue['technical_cost'])),
                                    // Chi phí hoàn tiền
                                    Stat::make(__('dashboard.general_stat.customer_cost'), Helper::formatPrice($revenue['customer_cost'])),
                                    // Chi phí vận chuyển
                                    Stat::make(__('dashboard.general_stat.transportation_cost'), Helper::formatPrice($revenue['transportation_cost'])),
                                    // Chi phí giảm giá
                                    Stat::make(__('dashboard.general_stat.discount_cost'), Helper::formatPrice($revenue['discount_cost'])),
                                    // Chi phí hoàn tiền
                                    Stat::make(__('dashboard.general_stat.refund_cost'), Helper::formatPrice($revenue['refund_cost'])),
                                ])

                        ]),
                ])
        ];
    }
}
