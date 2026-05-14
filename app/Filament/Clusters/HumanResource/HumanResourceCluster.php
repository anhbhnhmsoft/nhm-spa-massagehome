<?php

namespace App\Filament\Clusters\HumanResource;

use App\Enums\Admin\AdminGate;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class HumanResourceCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function canAccess(): bool
    {
        return Gate::allows(AdminGate::ALLOW_ADMIN);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.human_resource');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('admin.nav.human_resource');
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('admin.nav.human_resource');
    }
}
