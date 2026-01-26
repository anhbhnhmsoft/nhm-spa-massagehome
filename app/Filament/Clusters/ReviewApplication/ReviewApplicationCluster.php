<?php

namespace App\Filament\Clusters\ReviewApplication;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class ReviewApplicationCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('admin.nav.review_application');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.review_application');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('admin.nav.review_application');
    }
}
