<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv;

use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv\Pages\ViewLeaderKTV;
use App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv\Schemas\LeaderKTVInfolist;
use App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv\Tables\LeaderKTVsTable;
use App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv\Pages\ListLeaderKTV;
use App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv\Schemas\LeaderKTVForm;
use App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv\Pages\EditLeaderKTV;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaderKTVResource extends Resource
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
        return LeaderKTVForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaderKTVsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeaderKTVInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query = $query->with('profile', 'reviewApplication')
            ->whereIn('role', [UserRole::KTV->value, UserRole::CUSTOMER->value])
            ->whereHas('reviewApplication', function (Builder $query) {
                $query->whereIn('status', ReviewApplicationStatus::values());
                $query->where('role', UserRole::KTV->value);
                $query->where('is_leader', true);
            });
        return $query;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.ktv.label_leader');
    }

    public static function getModelLabel(): string
    {
        return __('admin.ktv.label_leader');
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaderKTV::route('/'),
            'edit' => EditLeaderKTV::route('/{record}/edit'),
            'view' => ViewLeaderKTV::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return User::query();
    }
}
