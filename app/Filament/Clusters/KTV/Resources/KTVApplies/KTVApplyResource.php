<?php

namespace App\Filament\Clusters\KTV\Resources\KTVApplies;

use App\Enums\UserRole;
use App\Filament\Clusters\KTV\KTVCluster;
use App\Filament\Clusters\KTV\Resources\KTVApplies\Pages\CreateKTVApply;
use App\Filament\Clusters\KTV\Resources\KTVApplies\Pages\ListKTVApplies;
use App\Filament\Clusters\KTV\Resources\KTVApplies\Pages\ViewKTVApply;
use App\Filament\Clusters\KTV\Resources\KTVApplies\Schemas\KTVApplyForm;
use App\Filament\Clusters\KTV\Resources\KTVApplies\Tables\KTVAppliesTable;
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
            'create' => CreateKTVApply::route('/create'),
            'view' => ViewKTVApply::route('/{record}'),
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

        return $query->where('role', UserRole::KTV->value)
            ->where('is_active', false)
            ->with('profile', 'reviewApplication', 'files')
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
