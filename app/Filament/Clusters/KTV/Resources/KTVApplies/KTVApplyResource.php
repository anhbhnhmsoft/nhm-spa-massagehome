<?php

namespace App\Filament\Clusters\KTV\Resources\KTVApplies;

use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\KTV\KTVCluster;
use App\Filament\Clusters\KTV\Resources\KTVApplies\Pages\ListKTVApplies;
use App\Filament\Clusters\KTV\Resources\KTVApplies\Pages\EditKTVApply;
use App\Filament\Clusters\KTV\Resources\KTVApplies\Pages\ViewKTVApply;
use App\Filament\Clusters\KTV\Resources\KTVApplies\Schemas\KTVApplyForm;
use App\Filament\Clusters\KTV\Resources\KTVApplies\Tables\KTVAppliesTable;
use App\Filament\Clusters\KTV\Resources\KTVs\Pages\CreateKTV;
use App\Filament\Clusters\KTV\Resources\KTVs\Pages\EditKTV;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KTVApplyResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = KTVCluster::class;

    protected static ?string $recordTitleAttribute = 'User';

    public static function form(Schema $schema): Schema
    {
        return KTVApplyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KTVAppliesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKTVApplies::route('/'),
            'create' => CreateKTV::route('/create'),
            'view' => ViewKTVApply::route('/{record}'),
            'edit' => EditKTV::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.ktv_apply.nav');
    }

    public static function getModelLabel(): string
    {
        return __('admin.ktv_apply.label');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return $query->whereIn('role', [UserRole::KTV->value, UserRole::CUSTOMER->value])
            ->with('profile', 'reviewApplication', 'files')
            ->whereRelation('reviewApplication', 'status', '!=', ReviewApplicationStatus::APPROVED->value)
            ->whereRelation('reviewApplication', 'role', UserRole::KTV->value)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
