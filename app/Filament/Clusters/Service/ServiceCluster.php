<?php

namespace App\Filament\Clusters\Service;

use App\Enums\Admin\AdminGate;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class ServiceCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    public static function canAccess(): bool
    {
        return Gate::allows(AdminGate::ALLOW_OPERATION);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.service');
    }


    public static function getClusterBreadcrumb(): ?string
    {
        return __('admin.nav.service');
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.service');
    }
}
