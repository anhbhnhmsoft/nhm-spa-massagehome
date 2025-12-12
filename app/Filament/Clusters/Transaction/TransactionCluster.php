<?php

namespace App\Filament\Clusters\Transaction;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class TransactionCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.transaction');
    }


    public static function getClusterBreadcrumb(): ?string
    {
        return __('admin.nav.transaction');
    }
}
