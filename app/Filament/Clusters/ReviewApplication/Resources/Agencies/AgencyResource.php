<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\Agencies;

use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\Pages\AgencyDashboard;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\Pages\EditAgency;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\Pages\ListAgencies;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\Pages\ViewAgencies;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\Schemas\AgencyForm;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\Schemas\AgencyInfolist;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\Tables\AgenciesTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AgencyResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;


    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('admin.nav.review_application');
    }

    protected static ?string $recordTitleAttribute = 'User';

    public static function form(Schema $schema): Schema
    {
        return AgencyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgenciesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AgencyInfolist::configure($schema);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query = $query->with('profile', 'reviewApplication')
            ->whereIn('role', [UserRole::AGENCY->value, UserRole::CUSTOMER->value])
            ->whereHas('reviewApplication', function (Builder $query) {
                $query->whereIn('status', ReviewApplicationStatus::values());
                $query->where('role', UserRole::AGENCY->value);
            })
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        return $query;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.agency.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.agency.label');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgencies::route('/'),
            'edit' => EditAgency::route('/{record}/edit'),
            'view' => ViewAgencies::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
