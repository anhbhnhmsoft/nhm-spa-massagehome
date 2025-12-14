<?php

namespace App\Filament\Clusters\Marketing\Resources\Coupons;

use App\Filament\Clusters\Marketing\MarketingCluster;
use App\Filament\Clusters\Marketing\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Clusters\Marketing\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Clusters\Marketing\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Clusters\Marketing\Resources\Coupons\Schemas\CouponForm;
use App\Filament\Clusters\Marketing\Resources\Coupons\Tables\CouponsTable;
use App\Models\Coupon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = MarketingCluster::class;

    protected static ?string $recordTitleAttribute = 'Coupon';

    public static function form(Schema $schema): Schema
    {
        return CouponForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CouponsTable::configure($table);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.coupon.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.coupon.label');
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
            'index' => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit' => EditCoupon::route('/{record}/edit'),
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
