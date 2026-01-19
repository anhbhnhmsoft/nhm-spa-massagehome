<?php

namespace App\Filament\Clusters\KTV\Resources\KTVs;

use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\KTV\Resources\KTVs\Tables\KTVsTable;
use App\Filament\Clusters\KTV\Resources\KTVs\Pages\ListKTVs;
use App\Filament\Clusters\KTV\Resources\KTVs\Schemas\KTVForm;
use App\Filament\Clusters\KTV\Resources\KTVs\Pages\EditKTV;
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

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.ktv');
    }

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

        $query = $query->with('profile', 'reviewApplication')
            ->whereIn('role', [UserRole::KTV->value, UserRole::CUSTOMER->value])
            ->whereHas('reviewApplication', function (Builder $query) {
                $query->whereIn('status', ReviewApplicationStatus::values());
                $query->where('role', UserRole::KTV->value);
            })
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        return $query;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.ktv.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.ktv.model_label');
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKTVs::route('/'),
            'edit' => EditKTV::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return User::query()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
