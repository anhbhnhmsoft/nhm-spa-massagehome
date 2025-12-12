<?php

namespace App\Filament\Clusters\Agency;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class AgencyCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.agency');
    }


    public static function getClusterBreadcrumb(): ?string
    {
        return __('admin.nav.agency');
    }
}
