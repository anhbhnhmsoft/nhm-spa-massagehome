<?php

namespace App\Filament\Clusters\Transaction\Resources\WalletTransactions\Tables;

use App\Enums\UserRole;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Enums\ConfigName;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\AgencyResource;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
use App\Filament\Clusters\User\Resources\Customers\CustomerResource;
use App\Filament\Components\CommonFields;
use App\Models\WalletTransaction;
use App\Services\ConfigService;
use App\Services\UserWithdrawInfoService;
use App\Services\WalletTransactionStatusService;
use App\Core\Service\ServiceReturn;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionsTable
{
    public static function configure(Table $table, bool $pending = false): Table
    {
        $table = $table
            ->recordUrl(null)
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['wallet.user']);
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                CommonFields::IdColumn()
                    ->searchable(),

                TextColumn::make('wallet.user.name')
                    ->label(__('admin.transaction.fields.user'))
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('wallet.user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                    })
                    ->color('primary') // Chuyển chữ sang màu xanh
                    ->weight('bold')
                    ->url(function ($record) {
                        $user = $record->wallet->user;
                        $role = $user->role;
                        return match ($role) {
                            UserRole::KTV->value => KTVResource::getUrl('view', ['record' => $user]),
                            UserRole::CUSTOMER->value => CustomerResource::getUrl('edit', ['record' => $user]),
                            UserRole::AGENCY->value => AgencyResource::getUrl('view', ['record' => $user]),
                            default => null,
                        };
                    })
                    ->formatStateUsing(function ($state, $record) {
                        $user = $record->wallet->user;
                        $contact = $user->phone ?? $user->email;
                        return "$user->name - ($contact)";
                    })
                    ->description(function ($record) {
                        $userRole = $record->wallet->user->role;
                        return UserRole::from($userRole)->label() ?? "";
                    }),
                TextColumn::make('type')
                    ->label(__('admin.transaction.fields.type'))
                    ->formatStateUsing(fn($state) => WalletTransactionType::tryFrom($state)?->label())
                    ->badge(),
                TextColumn::make('transaction_code')
                    ->label(__('admin.transaction.fields.code'))
                    ->searchable(),
                TextColumn::make('point_amount')
                    ->label(__('admin.transaction.fields.amount'))
                    ->numeric(2),
                TextColumn::make('status')
                    ->label(__('admin.transaction.fields.status'))
                    ->formatStateUsing(fn($state) => WalletTransactionStatus::tryFrom($state)?->label() ?? "")
                    ->color(fn($state) => match (WalletTransactionStatus::tryFrom($state)) {
                        WalletTransactionStatus::PENDING => 'warning',
                        WalletTransactionStatus::COMPLETED => 'success',
                        WalletTransactionStatus::FAILED, WalletTransactionStatus::CANCELLED => 'danger',
                        WalletTransactionStatus::REFUNDED => 'info',
                        default => 'secondary',
                    })
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('admin.transaction.fields.created_at'))
                    ->dateTime("d-M-Y H:i")
                    ->sortable(),
            ]);

        if ($pending) {
            $table = $table
                ->recordActions([
                    ActionGroup::make([
                        Action::make('approve')
                            ->label(__('admin.transaction.actions.approve'))
                            ->icon('heroicon-o-check')
                            ->color('success')
                            ->visible(fn(WalletTransaction $record): bool => self::isPendingIncome($record))
                            ->action(function (WalletTransaction $record, WalletTransactionStatusService $service): void {
                                self::notifyActionResult($service->approveTransaction($record));
                            })
                            ->requiresConfirmation()
                            ->modalDescription(__('admin.transaction.actions.approve_confirmation_message'))
                            ->modalSubmitActionLabel(__('admin.transaction.actions.approve'))
                            ->modalCancelActionLabel(__('admin.transaction.actions.cancel')),

                        Action::make('transfer')
                            ->label(__('admin.transaction.actions.transfer'))
                            ->icon('heroicon-o-arrow-right-on-rectangle')
                            ->color('info')
                            ->visible(fn(WalletTransaction $record): bool => self::isPendingWithdrawal($record))
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
                            ->schema(fn (WalletTransaction $record, ConfigService $service, UserWithdrawInfoService $withdrawInfoService): array => self::transferSchema($record, $service, $withdrawInfoService))
                            ->requiresConfirmation()
                            ->modalDescription(__('admin.transaction.actions.transfer_confirmation_message'))
                            ->modalFooterActions(fn ($action): array => self::confirmationFooterActions($action, 'success'))
                            ->modalSubmitActionLabel(__('admin.transaction.actions.approve')) 
                            ->modalCancelActionLabel(__('admin.transaction.actions.cancel')),

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
                            ->modalFooterActions(fn ($action): array => self::confirmationFooterActions($action, 'danger'))
                            ->modalSubmitActionLabel(__('admin.transaction.actions.approve')) // Nút "Xác nhận"
                            ->modalCancelActionLabel(__('admin.transaction.actions.cancel')),
                    ])->buttonGroup(),
                ])
                ->filtersLayout(FiltersLayout::AboveContent)
                ->filtersFormColumns(2)
                ->filters([
                    SelectFilter::make('type')
                        ->label(__('admin.transaction.fields.type'))
                        ->options(self::actionableTypeOptions()),
                ])
                ->defaultSort('created_at', 'desc')
                ->emptyStateHeading(__('admin.transaction.pending.empty_state.heading'));
        } else {
            $table = $table
                ->actions([
                    ActionGroup::make([
                        Action::make('approve')
                            ->label(__('admin.transaction.actions.approve'))
                            ->icon('heroicon-o-check')
                            ->color('success')
                            ->visible(fn(WalletTransaction $record): bool =>
                                $record->status == WalletTransactionStatus::PENDING->value &&
                                in_array((int)$record->type, [
                                    WalletTransactionType::DEPOSIT_WECHAT_PAY->value,
                                    WalletTransactionType::DEPOSIT_ALIPAY_PAY->value,
                                ], true)
                            )
                            ->action(function (WalletTransaction $record, WalletTransactionStatusService $service): void {
                                self::notifyActionResult($service->approveTransaction($record));
                            })
                            ->requiresConfirmation()
                            ->modalDescription(__('admin.transaction.actions.approve_confirmation_message'))
                            ->modalSubmitActionLabel(__('admin.transaction.actions.approve')) // Nút "Xác nhận"
                            ->modalCancelActionLabel(__('admin.transaction.actions.cancel')),

                        Action::make('transfer')
                            ->label(__('admin.transaction.actions.transfer'))
                            ->icon('heroicon-o-arrow-right-on-rectangle')
                            ->color('info')
                            ->visible(fn(WalletTransaction $record): bool => self::isPendingWithdrawal($record))
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
                            ->schema(fn (WalletTransaction $record, ConfigService $service, UserWithdrawInfoService $withdrawInfoService): array => self::transferSchema($record, $service, $withdrawInfoService))
                            ->requiresConfirmation()
                            ->modalDescription(__('admin.transaction.actions.transfer_confirmation_message'))
                            ->modalFooterActions(fn ($action): array => self::confirmationFooterActions($action, 'success'))
                            ->modalSubmitActionLabel(__('admin.transaction.actions.approve'))
                            ->modalCancelActionLabel(__('admin.transaction.actions.cancel')),

                        Action::make('cancel')
                            ->label(__('admin.transaction.actions.cancel'))
                            ->icon('heroicon-o-x-mark')
                            ->color('danger')
                            ->visible(fn(WalletTransaction $record): bool =>
                                $record->status == WalletTransactionStatus::PENDING->value &&
                                in_array((int)$record->type, [
                                    WalletTransactionType::DEPOSIT_WECHAT_PAY->value,
                                    WalletTransactionType::DEPOSIT_ALIPAY_PAY->value,
                                ], true)
                            )
                            ->action(function (WalletTransaction $record, WalletTransactionStatusService $service): void {
                                self::notifyActionResult($service->cancelTransaction($record));
                            })
                            ->requiresConfirmation()
                            ->modalDescription(__('admin.transaction.actions.cancel_confirmation_message'))
                            ->modalSubmitActionLabel(__('admin.transaction.actions.approve'))
                            ->modalCancelActionLabel(__('admin.transaction.actions.cancel')),
                    ])->buttonGroup(),
                ])
                ->filtersLayout(FiltersLayout::AboveContent)
                ->filtersFormColumns(5)
                ->filters([
                    SelectFilter::make('type')
                        ->label(__('admin.transaction.fields.type'))
                        ->options(WalletTransactionType::toOptions()),
                    SelectFilter::make('status')
                        ->label(__('admin.transaction.fields.status'))
                        ->options(WalletTransactionStatus::toOptions()),
                ])
                ->defaultSort('created_at', 'desc')
                ->emptyStateHeading(__('admin.transaction.empty_state.heading'));
        }

        return $table;
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

    private static function resolveTypeValue(WalletTransaction $record): ?int
    {
        $raw = $record->type;
        if (is_numeric($raw)) {
            return (int) $raw;
        }

        $name = (string) $raw;
        foreach (WalletTransactionType::cases() as $case) {
            if ($case->name === strtoupper($name) || $case->name === $name) {
                return $case->value;
            }
        }

        return null;
    }

    private static function isPendingActionable(WalletTransaction $record): bool
    {
        $type = self::resolveTypeValue($record);
        return $record->status == WalletTransactionStatus::PENDING->value
            && $type !== null
            && in_array($type, self::actionableTypes(), true);
    }

    private static function isPendingIncome(WalletTransaction $record): bool
    {
        $type = self::resolveTypeValue($record);
        return $record->status == WalletTransactionStatus::PENDING->value
            && $type !== null
            && in_array($type, WalletTransactionType::incomeStatus(), true);
    }

    private static function isPendingWithdrawal(WalletTransaction $record): bool
    {
        $type = self::resolveTypeValue($record);
        return $record->status == WalletTransactionStatus::PENDING->value
            && $type !== null
            && $type === WalletTransactionType::WITHDRAWAL->value;
    }

    private static function transferSchema(
        WalletTransaction $record,
        ConfigService $service,
        UserWithdrawInfoService $withdrawInfoService,
    ): array {
        $transferInfo = self::resolveTransferInfo($record, $service, $withdrawInfoService);
        if ($transferInfo->isError()) {
            return [
                ViewField::make('error')
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

            $info = $withdrawInfoRes->getData()['config'] ?? [];
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
        $submitLabel = __('common.action.submit');

        try {
            $name = method_exists($action, 'getName') ? $action->getName() : null;
            if ($name === 'approve') {
                $submitLabel = __('admin.transaction.actions.approve');
            } elseif ($name === 'transfer') {
                $submitLabel = __('admin.transaction.actions.transfer');
            } elseif ($name === 'cancel') {
                $submitLabel = __('admin.transaction.actions.cancel');
            }
        } catch (\Throwable) {
            // ignore and fallback to generic submit
        }

        return [
            $action->getModalCancelAction()
                ->label(__('common.action.close'))
                ->color('gray'),
            $action->getModalSubmitAction()
                ->label($submitLabel)
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
