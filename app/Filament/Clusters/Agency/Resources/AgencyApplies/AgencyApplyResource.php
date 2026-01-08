<?php

namespace App\Filament\Clusters\Agency\Resources\AgencyApplies;

use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\Agency\AgencyCluster;
use App\Filament\Clusters\Agency\Resources\AgencyApplies\Pages\CreateAgencyApply;
use App\Filament\Clusters\Agency\Resources\AgencyApplies\Pages\ListAgencyApplies;
use App\Filament\Clusters\Agency\Resources\AgencyApplies\Pages\ViewAgencyApply;
use App\Filament\Clusters\Agency\Resources\AgencyApplies\Schemas\AgencyApplyForm;
use App\Filament\Clusters\Agency\Resources\AgencyApplies\Tables\AgencyAppliesTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AgencyApplyResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \UnitEnum | null $navigationGroup = 'agency';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = AgencyCluster::class;

    protected static ?string $recordTitleAttribute = 'User';

    public static function form(Schema $schema): Schema
    {
        return AgencyApplyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgencyAppliesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function getNavigationLabel(): string
    {
        return __('admin.agency_apply.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.agency_apply.label');
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query->whereIn('role', [UserRole::AGENCY->value, UserRole::CUSTOMER->value])
            ->with('reviewApplication', 'files')
            ->whereRelation('reviewApplication', 'status', '!=', ReviewApplicationStatus::APPROVED->value)
            ->whereRelation('reviewApplication', 'role', UserRole::AGENCY->value);

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
    public static function getPages(): array
    {
        return [
            'index' => ListAgencyApplies::route('/'),
            'create' => CreateAgencyApply::route('/create'),
            'view' => ViewAgencyApply::route('/{record}'),
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
