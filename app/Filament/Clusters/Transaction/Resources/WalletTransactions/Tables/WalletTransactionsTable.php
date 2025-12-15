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
                TextColumn::make('amount')
                    ->label(__('admin.transaction.fields.amount'))
                    ->money(),
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
                                $record->wallet()->increment('balance', (int) $record->amount);
                            }
                        })
                        ->requiresConfirmation(),
                    Action::make('cancel')
                        ->label(__('admin.transaction.actions.cancel'))
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(fn($record) => $record->status === WalletTransactionStatus::PENDING->value)
                        ->action(fn($record) => $record->update(['status' => WalletTransactionStatus::FAILED]))
                        ->requiresConfirmation(),
                ])
            ]);
    }
}
