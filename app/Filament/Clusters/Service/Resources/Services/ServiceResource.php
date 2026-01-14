<?php

namespace App\Filament\Clusters\Service\Resources\Services;

use App\Filament\Clusters\Service\Resources\Services\Pages\CreateService;
use App\Filament\Clusters\Service\Resources\Services\Pages\EditService;
use App\Filament\Clusters\Service\Resources\Services\Pages\ListServices;
use App\Filament\Clusters\Service\Resources\Services\Schemas\ServiceForm;
use App\Filament\Clusters\Service\Resources\Services\Tables\ServicesTable;
use App\Filament\Clusters\Service\ServiceCluster;
use App\Models\Service;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.service');
    }

    protected static ?string $recordTitleAttribute = 'Service';

    public static function form(Schema $schema): Schema
    {
        return ServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicesTable::configure($table);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.service.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.service.label');
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

        return $query->with('category', 'provider')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
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
