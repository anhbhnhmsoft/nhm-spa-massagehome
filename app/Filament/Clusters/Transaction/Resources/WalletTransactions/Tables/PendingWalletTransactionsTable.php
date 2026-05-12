<?php

namespace App\Filament\Clusters\Transaction\Resources\WalletTransactions\Tables;

use App\Core\Service\ServiceReturn;
use App\Enums\ConfigName;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\WalletTransaction;
use App\Services\ConfigService;
use App\Services\UserWithdrawInfoService;
use App\Services\WalletTransactionStatusService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PendingWalletTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return WalletTransactionsTable::configure($table)
            ->filtersFormColumns(2)
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.transaction.fields.type'))
                    ->options(self::actionableTypeOptions()),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label(__('admin.transaction.actions.approve'))
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn (WalletTransaction $record): bool => self::isPendingIncome($record))
                        ->action(function (WalletTransaction $record, WalletTransactionStatusService $service): void {
                            self::notifyActionResult($service->approveTransaction($record));
                        })
                        ->requiresConfirmation()
                        ->modalDescription(__('admin.transaction.actions.approve_confirmation_message'))
                        ->modalFooterActions(fn ($action): array => self::confirmationFooterActions($action, 'success')),

                    Action::make('transfer')
                        ->label(__('admin.transaction.actions.transfer'))
                        ->icon('heroicon-o-arrow-right-on-rectangle')
                        ->color('info')
                        ->visible(fn (WalletTransaction $record): bool => self::isPendingWithdrawal($record))
                        ->action(function (
                            WalletTransaction $record,
                            WalletTransactionStatusService $service,
                            ConfigService $configService,
                            UserWithdrawInfoService $withdrawInfoService,
                        ): void {
                            $transferInfo = self::resolveTransferInfo($record, $configService, $withdrawInfoService);
                            if ($transferInfo->isError()) {
                                self::notifyActionResult($transferInfo);
                                return;
                            }

                            self::notifyActionResult($service->approveTransaction($record));
                        })
                        ->modal()
                        ->schema(fn (
                            WalletTransaction $record,
                            ConfigService $service,
                            UserWithdrawInfoService $withdrawInfoService,
                        ): array => self::transferSchema($record, $service, $withdrawInfoService))
                        ->requiresConfirmation()
                        ->modalDescription(__('admin.transaction.actions.transfer_confirmation_message'))
                        ->modalFooterActions(fn ($action): array => self::confirmationFooterActions($action, 'success')),

                    Action::make('cancel')
                        ->label(__('admin.transaction.actions.cancel'))
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(fn (WalletTransaction $record): bool => self::isPendingActionable($record))
                        ->action(function (WalletTransaction $record, WalletTransactionStatusService $service): void {
                            self::notifyActionResult($service->cancelTransaction($record));
                        })
                        ->requiresConfirmation()
                        ->modalDescription(__('admin.transaction.actions.cancel_confirmation_message'))
                        ->modalFooterActions(fn ($action): array => self::confirmationFooterActions($action, 'danger')),
                ])->buttonGroup(),
            ])
            ->emptyStateHeading(__('admin.transaction.pending.empty_state.heading'));
    }

    public static function actionableTypes(): array
    {
        return [
            ...WalletTransactionType::incomeStatus(),
            WalletTransactionType::WITHDRAWAL->value,
        ];
    }

    private static function actionableTypeOptions(): array
    {
        return array_intersect_key(
            WalletTransactionType::toOptions(),
            array_flip(self::actionableTypes()),
        );
    }

    private static function isPendingActionable(WalletTransaction $record): bool
    {
        return $record->status == WalletTransactionStatus::PENDING->value
            && in_array((int) $record->type, self::actionableTypes(), true);
    }

    private static function isPendingIncome(WalletTransaction $record): bool
    {
        return $record->status == WalletTransactionStatus::PENDING->value
            && in_array((int) $record->type, WalletTransactionType::incomeStatus(), true);
    }

    private static function isPendingWithdrawal(WalletTransaction $record): bool
    {
        return $record->status == WalletTransactionStatus::PENDING->value
            && (int) $record->type === WalletTransactionType::WITHDRAWAL->value;
    }

    private static function transferSchema(
        WalletTransaction $record,
        ConfigService $service,
        UserWithdrawInfoService $withdrawInfoService,
    ): array {
        $transferInfo = self::resolveTransferInfo($record, $service, $withdrawInfoService);
        if ($transferInfo->isError()) {
            return [
                Text::make('error')
                    ->content($transferInfo->getMessage()),
            ];
        }

        $info = $transferInfo->getData()['info'] ?? [];

        return [
            ViewField::make('transfer_info')
                ->view('filament.modal.transfer-money')
                ->viewData([
                    'info' => [
                        'bank_bin' => $info['bank_bin'] ?? '',
                        'bank_account' => $info['bank_account'] ?? '',
                        'bank_holder' => $info['bank_holder'] ?? '',
                        'amount' => $info['amount'] ?? 0,
                    ],
                    'record' => $record,
                ])
                ->columnSpanFull(),
        ];
    }

    private static function resolveTransferInfo(
        WalletTransaction $record,
        ConfigService $service,
        UserWithdrawInfoService $withdrawInfoService,
    ): ServiceReturn {
        try {
            $exchangeRate = (float) $service->getConfigValue(ConfigName::CURRENCY_EXCHANGE_RATE);
            $withdrawInfoRes = $withdrawInfoService->getDetailWithdrawInfoByUserId(
                userId: $record->wallet->user_id,
                withdrawInfoId: $record->foreign_key,
            );

            if ($withdrawInfoRes->isError()) {
                return ServiceReturn::error(__('admin.transaction.errors.withdraw_info_not_found'));
            }

            $info = $withdrawInfoRes->getData()->config ?? [];
            $info['amount'] = (float) $record->point_amount * $exchangeRate;

            return ServiceReturn::success([
                'info' => $info,
            ]);
        } catch (\Throwable) {
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    private static function confirmationFooterActions($action, string $submitColor): array
    {
        return [
            $action->getModalCancelAction()
                ->label(__('common.action.close'))
                ->color('gray'),
            $action->getModalSubmitAction()
                ->label(__('common.action.submit'))
                ->color($submitColor),
        ];
    }

    private static function notifyActionResult(ServiceReturn $result): void
    {
        if ($result->isSuccess()) {
            Notification::make()
                ->title(__('common.success.success'))
                ->body(__('common.success.data_updated'))
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('common.error.title'))
            ->body($result->getMessage())
            ->danger()
            ->send();
    }
}
