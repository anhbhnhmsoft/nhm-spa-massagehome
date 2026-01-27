<?php

namespace App\Filament\Clusters\User\Resources\Reviews;

use App\Filament\Clusters\User\Resources\Reviews\Pages\ListReviews;
use App\Filament\Clusters\User\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleBottomCenter;

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.user');
    }

    protected static ?string $recordTitleAttribute = 'Review';

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.review.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.review.label');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
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
