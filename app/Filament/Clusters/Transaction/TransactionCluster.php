<?php

namespace App\Filament\Clusters\Transaction;

use App\Enums\Admin\AdminGate;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class TransactionCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowsRightLeft;

    public static function canAccess(): bool
    {
        return Gate::allows(AdminGate::ALLOW_ACCOUNTANT);
    }
    public static function getNavigationLabel(): string
    {
        return __('admin.nav.transaction');
    }


    public static function getClusterBreadcrumb(): ?string
    {
        return __('admin.nav.transaction');
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.transaction');
    }
}
