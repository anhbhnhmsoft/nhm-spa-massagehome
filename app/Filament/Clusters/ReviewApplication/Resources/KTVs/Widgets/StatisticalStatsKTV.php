<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Widgets;

use App\Core\Helper;
use App\Enums\Admin\AdminGate;
use App\Services\DashboardService;
use App\Services\PaymentService;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

class StatisticalStatsKTV extends BaseWidget
{
    protected static bool $isLazy = true;
    public ?Model $record = null;

    protected function getStats(): array
    {
        $dashboardService = app(DashboardService::class);

        $generalKtvDashboard = $dashboardService->getGeneralKtvDashboard(
            userId: $this->record?->id,
        );

        $dataDashboard = $generalKtvDashboard->getData();

        return [
            Grid::make()
                ->columnSpanFull()
                ->columns(function () {
                    return Gate::allows(AdminGate::ALLOW_ACCOUNTANT) ? 5 : 3;
                })
                ->schema([
                    Stat::make(
                        label: __('admin.ktv.infolist.received_income'),
                        value: Helper::formatPrice($dataDashboard['received_income'] ?? 0)
                    )
                        ->visible(fn() => Gate::allows(AdminGate::ALLOW_ACCOUNTANT))
                        ->description(__('admin.ktv.infolist.received_income_desc'))
                        ->icon(Heroicon::CurrencyDollar)
                        ->color('success'),
                    Stat::make(
                        label: __('admin.ktv.infolist.total_customers'),
                        value: Helper::formatPrice($dataDashboard['total_customers'] ?? 0)
                    )
                        ->description(__('admin.ktv.infolist.total_customers_desc'))
                        ->icon(Heroicon::UserGroup)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.ktv.infolist.total_bookings'),
                        value: Helper::formatPrice($dataDashboard['total_bookings'] ?? 0)
                    )
                        ->description(__('admin.ktv.infolist.total_bookings_desc'))
                        ->icon(Heroicon::Calendar)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.ktv.infolist.affiliate_income'),
                        value: Helper::formatPrice($dataDashboard['affiliate_income'] ?? 0)
                    )
                        ->visible(fn() => Gate::allows(AdminGate::ALLOW_ACCOUNTANT))
                        ->description(__('admin.ktv.infolist.affiliate_income_desc'))
                        ->icon(Heroicon::ShoppingBag)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.ktv.infolist.total_reviews'),
                        value: Helper::formatPrice($dataDashboard['total_reviews'] ?? 0)
                    )
                        ->description(__('admin.ktv.infolist.total_reviews_desc'))
                        ->icon(Heroicon::ChatBubbleBottomCenter)
                        ->color('info'),
                ])
        ];
    }

}
