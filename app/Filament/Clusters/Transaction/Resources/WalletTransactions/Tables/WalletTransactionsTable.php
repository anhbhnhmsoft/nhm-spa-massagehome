<?php

namespace App\Filament\Clusters\Transaction\Resources\WalletTransactions\Tables;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class WalletTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('wallet.user.name')
                    ->label(__('admin.transaction.fields.user'))
                    ->searchable(),
                TextColumn::make('transaction_code')
                    ->label(__('admin.transaction.fields.code'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('admin.transaction.fields.type'))
                    ->formatStateUsing(fn($state) => WalletTransactionType::tryFrom($state)?->name ? __('admin.transaction.type.' . WalletTransactionType::tryFrom($state)->name) : '')
                    ->badge(),
                TextColumn::make('point_amount')
                    ->label(__('admin.transaction.fields.amount'))
                    ->numeric(2)
                    ->suffix(' P'),
                TextColumn::make('status')
                    ->label(__('admin.transaction.fields.status'))
                    ->formatStateUsing(fn($state) => WalletTransactionStatus::tryFrom($state)?->name ? __('admin.transaction.status.' . WalletTransactionStatus::tryFrom($state)->name) : '')
                    ->color(fn($state) => match (WalletTransactionStatus::tryFrom($state)) {
                        WalletTransactionStatus::PENDING => 'warning',
                        WalletTransactionStatus::COMPLETED => 'success',
                        WalletTransactionStatus::FAILED => 'danger',
                        default => 'secondary',
                    })
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('admin.transaction.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('type')
                    ->options(collect(WalletTransactionType::cases())->mapWithKeys(fn($case) => [$case->value => $case->name])),
                SelectFilter::make('status')
                    ->options(collect(WalletTransactionStatus::cases())->mapWithKeys(fn($case) => [$case->value => $case->name])),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label(__('admin.transaction.actions.approve'))
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn($record) => $record->status === WalletTransactionStatus::PENDING->value)
                        ->action(function ($record) {
                            $record->update(['status' => WalletTransactionStatus::COMPLETED]);
                            if (
                                $record->type === WalletTransactionType::DEPOSIT_QR_CODE->value ||
                                $record->type === WalletTransactionType::DEPOSIT_ZALO_PAY->value ||
                                $record->type === WalletTransactionType::DEPOSIT_MOMO_PAY->value
                            ) {
                                $record->wallet()->increment('balance', (float) $record->point_amount);
                            }
                        })
                        ->requiresConfirmation(),
                    Action::make('cancel')
                        ->label(__('admin.transaction.actions.cancel'))
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(fn($record) => $record->status === WalletTransactionStatus::PENDING->value)
                        ->action(function ($record) {
                            // Nếu là giao dịch rút tiền, hoàn tiền về ví khi hủy
                            if ($record->type === WalletTransactionType::WITHDRAWAL->value) {
                                $record->wallet()->increment('balance', (float) $record->point_amount);
                            }

                            $record->update(['status' => WalletTransactionStatus::FAILED->value]);
                        })
                        ->requiresConfirmation(),
                ])
            ]);
    }
}
