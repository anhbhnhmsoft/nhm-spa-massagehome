<?php

namespace App\Filament\Clusters\KTV;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class KTVCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    // public static function getNavigationGroup(): \UnitEnum|string|null
    // {
    //     return __('admin.nav.ktv');
    // }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.ktv');
    }


    public static function getClusterBreadcrumb(): ?string
    {
        return __('admin.nav.ktv');
    }
}
