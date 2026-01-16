<?php

namespace App\Filament\Clusters\Marketing\Resources\PageStatics;

use App\Filament\Clusters\Marketing\MarketingCluster;
use App\Filament\Clusters\Marketing\Resources\PageStatics\Pages\CreatePageStatic;
use App\Filament\Clusters\Marketing\Resources\PageStatics\Pages\EditPageStatic;
use App\Filament\Clusters\Marketing\Resources\PageStatics\Pages\ListPageStatics;
use App\Filament\Clusters\Marketing\Resources\PageStatics\Schemas\PageStaticForm;
use App\Filament\Clusters\Marketing\Resources\PageStatics\Tables\PageStaticsTable;
use App\Models\Page;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PageStaticResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.marketing');
    }

    protected static ?string $recordTitleAttribute = 'PageStatic';

    public static function form(Schema $schema): Schema
    {
        return PageStaticForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PageStaticsTable::configure($table);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.page_static.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.page_static.label');
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
            'index' => ListPageStatics::route('/'),
            'create' => CreatePageStatic::route('/create'),
            'edit' => EditPageStatic::route('/{record}/edit'),
        ];
    }
}
