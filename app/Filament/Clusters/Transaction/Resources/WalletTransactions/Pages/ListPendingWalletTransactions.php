<?php

namespace App\Filament\Clusters\Transaction\Resources\WalletTransactions\Pages;

use App\Enums\WalletTransactionStatus;
use App\Filament\Clusters\Transaction\Resources\WalletTransactions\Tables\PendingWalletTransactionsTable;
use App\Filament\Clusters\Transaction\Resources\WalletTransactions\WalletTransactionResource;
use App\Filament\Components\CommonActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListPendingWalletTransactions extends ListRecords
{
    protected static string $resource = WalletTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
        ];
    }

    public function getBreadcrumb(): string
    {
        return __('admin.transaction.pending.label');
    }

    public function getTitle(): string
    {
        return __('admin.transaction.pending.label');
    }

    public function table(Table $table): Table
    {
        return PendingWalletTransactionsTable::configure($table);
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        return static::getResource()::getEloquentQuery()
            ->where('status', WalletTransactionStatus::PENDING->value)
            ->whereIn('type', PendingWalletTransactionsTable::actionableTypes());
    }
}
