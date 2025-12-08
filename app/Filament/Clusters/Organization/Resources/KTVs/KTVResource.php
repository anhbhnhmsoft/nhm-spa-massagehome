<?php

namespace App\Filament\Clusters\Organization\Resources\KTVs;

use App\Enums\UserRole;
use App\Filament\Clusters\Organization\Resources\KTVs\Tables\KTVsTable;
use App\Filament\Clusters\Organization\Resources\KTVs\Pages\CreateKTV;
use App\Filament\Clusters\Organization\Resources\KTVs\Pages\ListKTVs;
use App\Filament\Clusters\Organization\Resources\KTVs\Schemas\KTVForm;
use App\Filament\Clusters\Organization\Resources\KTVs\Pages\EditKTV;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KTVResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;


    protected static ?string $recordTitleAttribute = 'User';

    public static function form(Schema $schema): Schema
    {
        return KTVForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KTVsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return $query->where('role', UserRole::KTV->value)
            ->with('profile')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('admin.nav.unit_organization');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.ktv.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.ktv.label');
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
            'index' => ListKTVs::route('/'),
            'create' => CreateKTV::route('/create'),
            'edit' => EditKTV::route('/{record}/edit'),
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
