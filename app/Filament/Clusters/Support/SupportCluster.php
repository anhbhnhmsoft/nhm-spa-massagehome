<?php

namespace App\Filament\Clusters\Support;

use App\Enums\Admin\AdminGate;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class SupportCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function canAccess(): bool
    {
        return Gate::allows(AdminGate::ALLOW_EMPLOYEE);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.support');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('admin.nav.support');
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('admin.nav.support');
    }
}
