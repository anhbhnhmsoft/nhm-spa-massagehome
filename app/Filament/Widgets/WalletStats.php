<?php

namespace App\Filament\Widgets;

use App\Core\Helper;
use App\Services\PaymentService;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class WalletStats extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        $paymentService = app(PaymentService::class);

        $userWalletInfo = $paymentService->getUserWallet(
            userId: $this->record?->id,
            withTotal: true
        );
        $dataWallet = $userWalletInfo->getData();

        return [
            Grid::make()
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    Stat::make(
                        label: __('admin.common.balance'),
                        value: Helper::formatPrice($dataWallet['wallet']->balance ?? 0)
                    )
                        ->description(__('admin.currency'))
                        ->icon(Heroicon::Wallet)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.common.total_income'),
                        value: Helper::formatPrice($dataWallet['total_income'] ?? 0)
                    )
                        ->icon(Heroicon::ArrowUp)
                        ->description(__('admin.currency'))
                        ->color('success'),
                    Stat::make(
                        label: __('admin.common.total_deposit'),
                        value: Helper::formatPrice($dataWallet['total_deposit'] ?? 0)
                    )
                        ->icon(Heroicon::ArrowUp)
                        ->description(__('admin.currency'))
                        ->color('success'),
                    Stat::make(
                        label: __('admin.common.total_withdrawal'),
                        value: Helper::formatPrice($dataWallet['total_withdrawal'] ?? 0)
                    )
                        ->icon(Heroicon::ArrowDown)
                        ->description(__('admin.currency'))
                        ->color('danger'),
                ]),
        ];
    }
}
