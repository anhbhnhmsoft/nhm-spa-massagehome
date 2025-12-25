<?php

namespace App\Filament\Clusters\Category;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class CategoryCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.category');
    }


    public static function getClusterBreadcrumb(): ?string
    {
        return __('admin.nav.category');
    }
}
