<?php

namespace App\Filament\Clusters\Service\Resources\Bookings;

use App\Filament\Clusters\Service\Resources\Bookings\Pages\ListBookings;
use App\Filament\Clusters\Service\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Clusters\Service\Resources\Bookings\Schemas\BookingInfoList;
use App\Filament\Clusters\Service\Resources\Bookings\Tables\BookingsTable;
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

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BookingInfoList::configure($schema);
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
            'view' => ViewBooking::route('/{record}'), // Thêm dòng này
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with([
                'ktvUser',
                'user',
                'service'
            ]);
    }
}
