<?php

namespace App\Filament\Clusters\Transaction\Resources\WalletTransactions;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Filament\Clusters\Transaction\Resources\WalletTransactions\Pages\ListWalletTransactions;
use App\Filament\Clusters\Transaction\Resources\WalletTransactions\Pages\ListPendingWalletTransactions;
use App\Filament\Clusters\Transaction\Resources\WalletTransactions\Schemas\WalletTransactionForm;
use App\Filament\Clusters\Transaction\Resources\WalletTransactions\Tables\WalletTransactionsTable;
use App\Filament\Clusters\Transaction\TransactionCluster;
use App\Models\WalletTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function canAccess(): bool
    {
        return TransactionCluster::canAccess();
    }
    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.transaction');
    }

    protected static ?string $recordTitleAttribute = 'Transaction';

    public static function form(Schema $schema): Schema
    {
        return WalletTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WalletTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.wallet_transaction.label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = WalletTransaction::query()
            ->where('status', WalletTransactionStatus::PENDING->value)
            ->whereIn('type', [
                ...WalletTransactionType::incomeStatus(),
                WalletTransactionType::WITHDRAWAL->value,
            ])
            ->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getModelLabel(): string
    {
        return __('admin.wallet_transaction.label');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWalletTransactions::route('/'),
            'pending' => ListPendingWalletTransactions::route('/pending'),
        ];
    }

}
