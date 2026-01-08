<?php

namespace App\Filament\Clusters\Agency\Resources\Agencies;

use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\Agency\AgencyCluster;
use App\Filament\Clusters\Agency\Resources\Agencies\Pages\CreateAgency;
use App\Filament\Clusters\Agency\Resources\Agencies\Pages\EditAgency;
use App\Filament\Clusters\Agency\Resources\Agencies\Pages\ListAgencies;
use App\Filament\Clusters\Agency\Resources\Agencies\Schemas\AgencyForm;
use App\Filament\Clusters\Agency\Resources\Agencies\Tables\AgenciesTable;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = AgencyCluster::class;

    protected static string | \UnitEnum | null $navigationGroup = 'agency';

    protected static ?string $recordTitleAttribute = 'User';

    public static function form(Schema $schema): Schema
    {
        return AgencyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgenciesTable::configure($table);
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

        $query = $query->where('role', UserRole::AGENCY->value)
            ->with('reviewApplication', 'files')
            ->whereRelation('reviewApplication', 'status', ReviewApplicationStatus::APPROVED->value)
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
            'create' => CreateAgency::route('/create'),
            'edit' => EditAgency::route('/{record}/edit'),
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
