<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv\Widgets;

use App\Services\DashboardService;
use App\Services\PaymentService;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatisticalStatsKTV extends BaseWidget
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

        $generalKtvDashboard = $dashboardService->getGeneralKtvDashboard(
            userId: $this->record?->id,
        );

        $dataWallet = $userWalletInfo->getData();
        $dataDashboard = $generalKtvDashboard->getData();

        return [
            Grid::make()
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Stat::make(
                        label: __('admin.ktv.infolist.balance'),
                        value: number_format(($dataWallet['wallet']->balance ?? 0), 2)
                    )
                        ->description(__('admin.currency'))
                        ->icon(Heroicon::Wallet)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.ktv.infolist.total_deposit'),
                        value: number_format(($dataWallet['total_deposit'] ?? 0), 2)
                    )
                        ->icon(Heroicon::ArrowUp)
                        ->description(__('admin.currency'))
                        ->color('success'),
                    Stat::make(
                        label: __('admin.ktv.infolist.total_withdrawal'),
                        value: number_format(($dataWallet['total_withdrawal'] ?? 0), 2)
                    )
                        ->icon(Heroicon::ArrowDown)
                        ->description(__('admin.currency'))
                        ->color('danger'),
                ]),
            Grid::make()
                ->columnSpanFull()
                ->columns(6)
                ->schema([
                    Stat::make(
                        label: __('admin.ktv.infolist.total_income'),
                        value: number_format(($dataDashboard['total_income'] ?? 0), 2)
                    )
                        ->description(__('admin.ktv.infolist.total_income_desc'))
                        ->icon(Heroicon::CurrencyDollar)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.ktv.infolist.received_income'),
                        value: number_format(($dataDashboard['received_income'] ?? 0), 2)
                    )
                        ->description(__('admin.ktv.infolist.received_income_desc'))
                        ->icon(Heroicon::CurrencyDollar)
                        ->color('success'),
                    Stat::make(
                        label: __('admin.ktv.infolist.total_customers'),
                        value: number_format(($dataDashboard['total_customers'] ?? 0), 0)
                    )
                        ->description(__('admin.ktv.infolist.total_customers_desc'))
                        ->icon(Heroicon::UserGroup)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.ktv.infolist.total_bookings'),
                        value: number_format(($dataDashboard['total_bookings'] ?? 0), 0)
                    )
                        ->description(__('admin.ktv.infolist.total_bookings_desc'))
                        ->icon(Heroicon::Calendar)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.ktv.infolist.affiliate_income'),
                        value: number_format(($dataDashboard['affiliate_income'] ?? 0), 0)
                    )
                        ->description(__('admin.ktv.infolist.affiliate_income_desc'))
                        ->icon(Heroicon::ShoppingBag)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.ktv.infolist.total_reviews'),
                        value: number_format(($dataDashboard['total_reviews'] ?? 0), 0)
                    )
                        ->description(__('admin.ktv.infolist.total_reviews_desc'))
                        ->icon(Heroicon::ChatBubbleBottomCenter)
                        ->color('info'),
                ])
        ];
    }

}
