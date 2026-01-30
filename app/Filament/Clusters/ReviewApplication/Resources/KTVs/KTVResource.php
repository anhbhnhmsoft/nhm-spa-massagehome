<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs;

use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Pages\ViewKTV;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Schemas\KTVInfolist;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Tables\KTVsTable;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Pages\ListKTVs;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Schemas\KTVForm;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Pages\EditKTV;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::User;

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('admin.nav.review_application');
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

    public static function infolist(Schema $schema): Schema
    {
        return KTVInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query = $query->with('profile', 'reviewApplication')
            ->whereIn('role', [UserRole::KTV->value, UserRole::CUSTOMER->value])
            ->whereHas('reviewApplication', function (Builder $query) {
                $query->whereIn('status', ReviewApplicationStatus::values());
                $query->where('role', UserRole::KTV->value);
            });
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
            'view' => ViewKTV::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return User::query()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
