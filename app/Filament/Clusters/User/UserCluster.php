<?php

namespace App\Filament\Clusters\User;

use App\Enums\Admin\AdminGate;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class UserCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::User;

    public static function canAccess(): bool
    {
        return Gate::allows(AdminGate::ALLOW_PROFILE);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.user');
    }


    public static function getClusterBreadcrumb(): ?string
    {
        return __('admin.nav.user');
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.user');
    }
}
