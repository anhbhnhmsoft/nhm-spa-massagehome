<?php

namespace App\Filament\Clusters\Transaction\Resources\WalletTransactions\Tables;

use App\Enums\ConfigName;
use App\Enums\NotificationType;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Filament\Components\CommonColumns;
use App\Jobs\SendNotificationJob;
use App\Services\ConfigService;
use App\Services\PaymentService;
use App\Services\WalletService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ViewField;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class WalletTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                CommonColumns::IdColumn(),

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
                    // Approve transaction
                    Action::make('approve')
                        ->label(__('admin.transaction.actions.approve'))
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(function ($record) {
                           return $record->status === WalletTransactionStatus::PENDING->value;
                        })
                        ->action(function ($record, PaymentService $paymentService) {
                            $paymentService->handleAdminConfirmTransaction($record);
                        })
                        ->requiresConfirmation()
                        ->hidden(fn($record) => $record->type === WalletTransactionType::WITHDRAWAL->value),
                    Action::make('cancel')
                        ->label(__('admin.transaction.actions.cancel'))
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(fn($record) => $record->status === WalletTransactionStatus::PENDING->value)
                        ->action(function ($record) {
                            // Nếu là giao dịch rút tiền, hoàn tiền về ví khi hủy
                            if ($record->type === WalletTransactionType::WITHDRAWAL->value) {
                                $record->wallet()->increment('balance', (float)$record->point_amount);
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
                    Action::make('transfer')
                        ->label(__('admin.transaction.actions.transfer'))
                        ->icon('heroicon-o-arrow-right-on-rectangle')
                        ->color('info')
                        ->visible(fn($record) => $record->type === WalletTransactionType::WITHDRAWAL->value)
                        ->action(function ($record) {
                            $record->update(['status' => WalletTransactionStatus::COMPLETED->value]);
                        })
                        ->hidden(fn($record) => $record->status !== WalletTransactionStatus::PENDING->value)
                        ->modal(true)
                        ->schema(function ($record, ConfigService $service) {
                            try {
                                $service = app(ConfigService::class);
                                $res = $service->getConfig(ConfigName::CURRENCY_EXCHANGE_RATE);
                                if ($res->isError()) {
                                    throw new \Exception($res->getMessage());
                                }
                                $rate = $res->getData()['config_value'];

                                $info = $record->drawInfo->config ?? [];
                                $info['amount'] = $record->point_amount * $rate;
                            } catch (\Throwable $th) {
                                return [
                                    Placeholder::make('error')
                                        ->content($th->getMessage())
                                ];
                            }
                            return [
                                ViewField::make('transfer_info')
                                    ->view('filament.modal.transfer-money')
                                    ->viewData([
                                        'info' => $info,
                                        'record' => $record
                                    ])
                                    ->columnSpanFull()];
                        })
                        ->requiresConfirmation(),
                ])
            ])
            ->emptyStateHeading(__('admin.transaction.empty_state.heading'))
            ->defaultSort('created_at', 'desc');
    }
}
