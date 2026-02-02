<?php

namespace App\Filament\Clusters\User\Resources\Customers\Widgets;

use App\Services\PaymentService;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class WalletCustomer extends BaseWidget
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
                ->columns(3)
                ->schema([
                    Stat::make(
                        label: __('admin.common.balance'),
                        value: number_format(($dataWallet['wallet']->balance ?? 0), 2)
                    )
                        ->description(__('admin.currency'))
                        ->icon(Heroicon::Wallet)
                        ->color('info'),
                    Stat::make(
                        label: __('admin.common.total_deposit'),
                        value: number_format(($dataWallet['total_deposit'] ?? 0), 2)
                    )
                        ->icon(Heroicon::ArrowUp)
                        ->description(__('admin.currency'))
                        ->color('success'),
                    Stat::make(
                        label: __('admin.common.total_withdrawal'),
                        value: number_format(($dataWallet['total_withdrawal'] ?? 0), 2)
                    )
                        ->icon(Heroicon::ArrowDown)
                        ->description(__('admin.currency'))
                        ->color('danger'),
                ]),
        ];
    }
}
