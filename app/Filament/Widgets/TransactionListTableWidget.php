<?php

namespace App\Filament\Widgets;

use App\Filament\Clusters\Transaction\Resources\WalletTransactions\Tables\WalletTransactionsTable;
use App\Repositories\WalletTransactionRepository;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class TransactionListTableWidget extends TableWidget
{
    protected static bool $isLazy = true;
    public ?Model $record = null;

    protected int | string | array $columnSpan = 1;

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('admin.ktv.infolist.transaction_list');
    }

    public function table(Table $table): Table
    {
        $walletTransactionRepository = app(WalletTransactionRepository::class);
        $walletId = $this->record?->wallet?->id;
        return WalletTransactionsTable::configure($table)
            ->defaultPaginationPageOption(5)
            ->filters([])
            ->query(function () use ($walletTransactionRepository, $walletId) {
                return $walletTransactionRepository->query()
                    ->with([
                        'wallet',
                        'wallet.user',
                    ])
                    ->where('wallet_id', $walletId);
            });
    }
}
