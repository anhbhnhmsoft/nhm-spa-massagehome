<?php

namespace App\Filament\Clusters\Support\Resources\SupportCategories;

use App\Enums\Admin\AdminGate;
use App\Filament\Clusters\Support\Resources\SupportCategories\Pages\CreateSupportCategory;
use App\Filament\Clusters\Support\Resources\SupportCategories\Pages\EditSupportCategory;
use App\Filament\Clusters\Support\Resources\SupportCategories\Pages\ListSupportCategories;
use App\Filament\Clusters\Support\Resources\SupportCategories\Schemas\SupportCategoryForm;
use App\Filament\Clusters\Support\Resources\SupportCategories\Tables\SupportCategoriesTable;
use App\Models\SupportCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class SupportCategoryResource extends Resource
{
    protected static ?string $model = SupportCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function canViewAny(): bool
    {
        return Gate::allows(AdminGate::ALLOW_ADMIN);
    }

    public static function canCreate(): bool
    {
        return Gate::allows(AdminGate::ALLOW_ADMIN);
    }

    public static function canEdit($record): bool
    {
        return Gate::allows(AdminGate::ALLOW_ADMIN);
    }

    public static function canDelete($record): bool
    {
        return Gate::allows(AdminGate::ALLOW_ADMIN);
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('admin.nav.support');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.common.support_category.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.common.support_category.label');
    }

    public static function form(Schema $schema): Schema
    {
        return SupportCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportCategories::route('/'),
            'create' => CreateSupportCategory::route('/create'),
            'edit' => EditSupportCategory::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery();
    }
}
