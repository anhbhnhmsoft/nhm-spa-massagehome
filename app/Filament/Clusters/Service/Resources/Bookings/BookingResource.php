<?php

namespace App\Filament\Clusters\Service\Resources\Bookings;

use App\Filament\Clusters\Service\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Clusters\Service\Resources\Bookings\Pages\EditBooking;
use App\Filament\Clusters\Service\Resources\Bookings\Pages\ListBookings;
use App\Filament\Clusters\Service\Resources\Bookings\Schemas\BookingForm;
use App\Filament\Clusters\Service\Resources\Bookings\Tables\BookingsTable;
use App\Filament\Clusters\Service\ServiceCluster;
use App\Models\ServiceBooking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingResource extends Resource
{
    protected static ?string $model = ServiceBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.service');
    }

    protected static ?string $recordTitleAttribute = 'ServiceBooking';

    public static function getNavigationLabel(): string
    {
        return __('admin.booking.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.booking.label');
    }

    public static function form(Schema $schema): Schema
    {
        return BookingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
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
            'index' => ListBookings::route('/'),
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
