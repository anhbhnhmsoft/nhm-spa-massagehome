<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\Agencies\Widgets;

use App\Enums\DateRangeDashboard;
use App\Services\DashboardService;
use App\Services\PaymentService;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class StatisticalStatsAgency extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        $paymentService = app(PaymentService::class);
        $dashboardService = app(DashboardService::class);

        $userWalletInfo = $paymentService->getUserWallet(
            userId: $this->record?->id,
            withTotal: true
        );

        $generalAgencyDashboard = $dashboardService->getAgencyDashboardData(
            userId: $this->record?->id,
            range: DateRangeDashboard::ALL
        );

        $dataWallet = $userWalletInfo->getData();
        $dataDashboard = $generalAgencyDashboard->getData();

        return [
            Grid::make()
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Stat::make(
                        label: __('admin.agency.infolist.balance'),
                        value: number_format(($dataWallet['wallet']->balance ?? 0), 2)
                    )
                        ->description(__('admin.currency'))
                        ->icon(Heroicon::Wallet)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.agency.infolist.total_deposit'),
                        value: number_format(($dataWallet['total_deposit'] ?? 0), 2)
                    )
                        ->icon(Heroicon::ArrowUp)
                        ->description(__('admin.currency'))
                        ->color('success'),
                    Stat::make(
                        label: __('admin.agency.infolist.total_withdrawal'),
                        value: number_format(($dataWallet['total_withdrawal'] ?? 0), 2)
                    )
                        ->icon(Heroicon::ArrowDown)
                        ->description(__('admin.currency'))
                        ->color('danger'),
                ]),
            Grid::make()
                ->columnSpanFull()
                ->columns(5)
                ->schema([
                    Stat::make(
                        label: __('admin.agency.infolist.total_profit_referral_ktv'),
                        value: number_format(($dataDashboard['total_profit_referral_ktv'] ?? 0), 2)
                    )
                        ->description(__('admin.agency.infolist.total_profit_referral_ktv_desc'))
                        ->icon(Heroicon::CurrencyDollar)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.agency.infolist.total_profit_affiliate'),
                        value: number_format(($dataDashboard['total_profit_affiliate'] ?? 0), 2)
                    )
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
