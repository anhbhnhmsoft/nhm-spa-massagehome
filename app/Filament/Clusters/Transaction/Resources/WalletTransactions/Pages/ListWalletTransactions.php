<?php

namespace App\Filament\Clusters\Transaction\Resources\WalletTransactions\Pages;

use App\Filament\Clusters\Transaction\Resources\WalletTransactions\WalletTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWalletTransactions extends ListRecords
{
    protected static string $resource = WalletTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.list');
    }
}
