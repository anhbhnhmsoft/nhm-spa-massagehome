<?php

namespace App\Filament\Clusters\Service\Resources\Categories;

use App\Enums\Admin\AdminGate;
use App\Filament\Clusters\Service\Resources\Categories\Pages\CreateCategory;
use App\Filament\Clusters\Service\Resources\Categories\Pages\EditCategory;
use App\Filament\Clusters\Service\Resources\Categories\Pages\ListCategories;
use App\Filament\Clusters\Service\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Clusters\Service\Resources\Categories\Tables\CategoriesTable;
use App\Filament\Clusters\Service\ServiceCluster;
use App\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function canViewAny(): bool
    {
        return Gate::allows(AdminGate::ALLOW_FULL);
    }

    public static function canCreate(): bool
    {
        return Gate::allows(AdminGate::ALLOW_ADMIN);
    }
    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.service');
    }


    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.category.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.category.label');
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery();
    }
}
