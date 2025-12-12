<?php

namespace App\Filament\Clusters\User\Resources\Reviews;

use App\Filament\Clusters\User\Resources\Reviews\Pages\CreateReview;
use App\Filament\Clusters\User\Resources\Reviews\Pages\EditReview;
use App\Filament\Clusters\User\Resources\Reviews\Pages\ListReviews;
use App\Filament\Clusters\User\Resources\Reviews\Schemas\ReviewForm;
use App\Filament\Clusters\User\Resources\Reviews\Tables\ReviewsTable;
use App\Filament\Clusters\User\UserCluster;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $recordTitleAttribute = 'Review';

    public static function form(Schema $schema): Schema
    {
        return ReviewForm::configure($schema);
    }

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
            'create' => CreateReview::route('/create'),
            'edit' => EditReview::route('/{record}/edit'),
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
