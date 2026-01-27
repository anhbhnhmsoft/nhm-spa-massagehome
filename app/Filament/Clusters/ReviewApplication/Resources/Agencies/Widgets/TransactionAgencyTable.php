<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\Agencies\Widgets;

use App\Filament\Clusters\Transaction\Resources\WalletTransactions\Tables\WalletTransactionsTable;
use App\Repositories\WalletTransactionRepository;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionAgencyTable extends TableWidget
{
    public ?Model $record = null;

    protected int | string | array $columnSpan = 2;

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('admin.agency.infolist.transaction_list');
    }

    public function table(Table $table): Table
    {
        $walletTransactionRepository = app(WalletTransactionRepository::class);
        $walletId = $this->record?->wallet?->id;
        return WalletTransactionsTable::configure($table)
            ->defaultPaginationPageOption(5)
            ->query(function () use ($walletTransactionRepository, $walletId) {
                return $walletTransactionRepository->query()
                    ->with([
                        'wallet',
                        'wallet.user',
                    ])
                    ->where('wallet_id', $walletId)
                    ->withoutGlobalScopes([
                        SoftDeletingScope::class,
                    ]);
            });
    }
}
