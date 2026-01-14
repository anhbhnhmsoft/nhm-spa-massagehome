<?php

namespace App\Filament\Clusters\Marketing;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class MarketingCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;


    public static function getNavigationLabel(): string
    {
        return __('admin.nav.marketing');
    }


    public static function getClusterBreadcrumb(): ?string
    {
        return __('admin.nav.marketing');
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.marketing');
    }
}
