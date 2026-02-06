<?php

namespace App\Filament\Clusters\Transaction\Resources\WalletTransactions\Tables;

use App\Enums\ConfigName;
use App\Enums\Jobs\WalletTransCase;
use App\Enums\NotificationType;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Filament\Components\CommonFields;
use App\Jobs\SendNotificationJob;
use App\Jobs\WalletTransactionJob;
use App\Services\ConfigService;
use App\Services\PaymentService;
use App\Services\UserWithdrawInfoService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Text;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['wallet.user']);
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                CommonFields::IdColumn(),

                TextColumn::make('wallet.user.name')
                    ->label(__('admin.transaction.fields.user'))
                    ->description(fn($record) => $record->wallet->user->phone),
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
            ->recordActions([
                ActionGroup::make([
                    // Duyệt giao dịch
                    Action::make('approve')
                        ->label(__('admin.transaction.actions.approve'))
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(function ($record) {
                           return $record->status == WalletTransactionStatus::PENDING->value
                                   // Chỉ duyệt các giao dịch nạp vào hệ thống
                               && in_array($record->type, WalletTransactionType::statusIn());
                        })
                        ->action(function ($record, PaymentService $paymentService) {
                            $paymentService->handleAdminConfirmTransaction($record);
                        })
                        ->requiresConfirmation(),

                    Action::make('fee_transfer')
                        ->visible(function ($record) {
                            return $record->type == WalletTransactionType::FEE_WITHDRAW->value;
                        })
                        ->label(fn($record) => __('admin.transaction.actions.fee_transfer', ['transaction_id' => $record->foreign_key]))
                        ->color('secondary')
                        ->disabled(),

                    // Chuyển tiền rút tiền
                    Action::make('transfer')
                        ->label(__('admin.transaction.actions.transfer'))
                        ->icon('heroicon-o-arrow-right-on-rectangle')
                        ->color('info')
                        ->visible(function ($record) {
                            return $record->status == WalletTransactionStatus::PENDING->value
                                    // Chỉ duyệt các giao dịch rút tiền
                                    && $record->type == WalletTransactionType::WITHDRAWAL->value;
                        })
                        ->action(function ($record) {
                            WalletTransactionJob::dispatchSync(
                                case: WalletTransCase::CONFIRM_WITHDRAW_REQUEST,
                                data: [
                                    'transaction_id' => $record->id,
                                ],
                            );
                        })
                        ->modal()
                        ->schema(function ($record, ConfigService $service, UserWithdrawInfoService $withdrawInfoService) {
                            try {
                                $exchangeRate = $service->getConfigValue(ConfigName::CURRENCY_EXCHANGE_RATE);
                                $withdrawInfoRes = $withdrawInfoService->getDetailWithdrawInfoByUserId(
                                    userId: $record->wallet->user_id,
                                    withdrawInfoId: $record->foreign_key,
                                );
                                if ($withdrawInfoRes->isError()) {
                                    return [
                                        Text::make('error')
                                            ->content(__('admin.transaction.errors.withdraw_info_not_found'))
                                    ];
                                }
                                $info = $withdrawInfoRes->getData()->config ?? [];
                                $info['amount'] = $record->point_amount * $exchangeRate;
                            } catch (\Throwable $th) {
                                return [
                                    Text::make('error')
                                        ->content(__('common_error.server_error'))
                                ];
                            }
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
                                        'record' => $record
                                    ])
                                    ->columnSpanFull()];
                        })
                        ->requiresConfirmation(),


                    // Hủy giao dịch
                    Action::make('cancel')
                        ->label(__('admin.transaction.actions.cancel'))
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(function ($record) {
                            return $record->status == WalletTransactionStatus::PENDING->value
                                && (in_array($record->type, WalletTransactionType::statusIn())
                                || $record->type === WalletTransactionType::WITHDRAWAL->value);
                        })
                        ->action(function ($record) {
                            // Nếu là giao dịch rút tiền, hoàn tiền về ví khi hủy
                            if ($record->type == WalletTransactionType::WITHDRAWAL->value) {
                                WalletTransactionJob::dispatchSync(
                                    case: WalletTransCase::CANCEL_WITHDRAW_REQUEST,
                                    data: [
                                        'transaction_id' => $record->id,
                                    ],
                                );
                            }
                            SendNotificationJob::dispatch(
                                userId: $record->wallet->user_id,
                                type: NotificationType::WALLET_TRANSACTION_CANCELLED,
                                data: [
                                    'transaction_code' => $record->transaction_code,
                                ]
                            );
                            $record->update(['status' => WalletTransactionStatus::FAILED->value]);
                        })
                        ->requiresConfirmation(),

                ])->buttonGroup()
            ])
            ->emptyStateHeading(__('admin.transaction.empty_state.heading'));
    }
}
