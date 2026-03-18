<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\Agencies\Widgets;

use App\Enums\Admin\AdminGate;
use App\Enums\DateRangeDashboard;
use App\Services\DashboardService;
use App\Services\PaymentService;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class StatisticalStatsAgency extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {

        $dashboardService = app(DashboardService::class);

        $generalAgencyDashboard = $dashboardService->getAgencyDashboardData(
            userId: $this->record?->id,
            range: DateRangeDashboard::ALL
        );


        $dataDashboard = $generalAgencyDashboard->getData();

        return [
            Grid::make()
                ->columnSpanFull()
                ->columns(function () {
                    return Gate::allows(AdminGate::ALLOW_ACCOUNTANT) ? 5 : 3;
                })
                ->schema([
                    Stat::make(
                        label: __('admin.agency.infolist.total_profit_referral_ktv'),
                        value: number_format(($dataDashboard['total_profit_referral_ktv'] ?? 0), 2)
                    )
                        ->visible(fn() => Gate::allows(AdminGate::ALLOW_ACCOUNTANT))
                        ->description(__('admin.agency.infolist.total_profit_referral_ktv_desc'))
                        ->icon(Heroicon::CurrencyDollar)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.agency.infolist.total_profit_affiliate'),
                        value: number_format(($dataDashboard['total_profit_affiliate'] ?? 0), 2)
                    )
                        ->visible(fn() => Gate::allows(AdminGate::ALLOW_ACCOUNTANT))
                        ->description(__('admin.agency.infolist.total_profit_affiliate'))
                        ->icon(Heroicon::CurrencyDollar)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.agency.infolist.total_referral_customer'),
                        value: number_format(($dataDashboard['total_referral_customer'] ?? 0), 2)
                    )
                        ->description(__('admin.agency.infolist.total_referral_customer_desc'))
                        ->icon(Heroicon::UserGroup)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.agency.infolist.total_customer_order_ktv'),
                        value: number_format(($dataDashboard['total_customer_order_ktv'] ?? 0), 2)
                    )
                        ->description(__('admin.agency.infolist.total_customer_order_ktv_desc'))
                        ->icon(Heroicon::UserGroup)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.agency.infolist.total_customer_affiliate_order'),
                        value: number_format(($dataDashboard['total_customer_affiliate_order'] ?? 0), 2)
                    )
                        ->description(__('admin.agency.infolist.total_customer_affiliate_order'))
                        ->icon(Heroicon::UserGroup)
                        ->color('info'),

                ])
        ];
    }
}
