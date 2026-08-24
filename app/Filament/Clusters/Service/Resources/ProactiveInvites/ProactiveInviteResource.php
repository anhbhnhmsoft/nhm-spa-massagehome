<?php

namespace App\Filament\Clusters\Service\Resources\ProactiveInvites;

use App\Enums\Admin\AdminGate;
use App\Filament\Clusters\Service\Resources\ProactiveInvites\Pages\ListProactiveInvites;
use App\Filament\Clusters\Service\Resources\ProactiveInvites\Tables\ProactiveInvitesTable;
use App\Models\KtvProactiveInvite;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ProactiveInviteResource extends Resource
{
    protected static ?string $model = KtvProactiveInvite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    public static function canViewAny(): bool
    {
        return Gate::allows(AdminGate::ALLOW_PROFILE);
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.service');
    }

    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationLabel(): string
    {
        return __('admin.proactive_invite.nav');
    }

    public static function getModelLabel(): string
    {
        return __('admin.proactive_invite.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.proactive_invite.plural');
    }

    public static function table(Table $table): Table
    {
        return ProactiveInvitesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProactiveInvites::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with(['ktv', 'customer']);
    }
}
