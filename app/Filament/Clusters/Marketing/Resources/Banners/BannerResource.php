<?php

namespace App\Filament\Clusters\Marketing\Resources\Banners;

use App\Filament\Clusters\Marketing\MarketingCluster;
use App\Filament\Clusters\Marketing\Resources\Banners\Pages\CreateBanner;
use App\Filament\Clusters\Marketing\Resources\Banners\Pages\EditBanner;
use App\Filament\Clusters\Marketing\Resources\Banners\Pages\ListBanners;
use App\Filament\Clusters\Marketing\Resources\Banners\Schemas\BannerForm;
use App\Filament\Clusters\Marketing\Resources\Banners\Tables\BannersTable;
use App\Models\Banner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.marketing');
    }

    protected static ?string $recordTitleAttribute = 'Banner';

    public static function form(Schema $schema): Schema
    {
        return BannerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BannersTable::configure($table);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.banner.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.banner.label');
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBanners::route('/'),
            'create' => CreateBanner::route('/create'),
            'edit' => EditBanner::route('/{record}/edit'),
        ];
    }
}
