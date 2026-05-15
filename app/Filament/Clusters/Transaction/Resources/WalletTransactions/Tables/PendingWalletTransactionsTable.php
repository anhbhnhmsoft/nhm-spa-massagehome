<?php

namespace App\Filament\Clusters\Transaction\Resources\WalletTransactions\Tables;

use App\Enums\WalletTransactionType;
use App\Filament\Clusters\Transaction\Resources\WalletTransactions\Tables\WalletTransactionsTable;
use Filament\Tables\Table;

class PendingWalletTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return WalletTransactionsTable::configure($table, true);
    }

    public static function actionableTypes(): array
    {
        return [
            ...WalletTransactionType::incomeStatus(),
            WalletTransactionType::WITHDRAWAL->value,
        ];
    }
}
