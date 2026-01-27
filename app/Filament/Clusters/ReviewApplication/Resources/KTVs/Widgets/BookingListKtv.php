<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Widgets;

use App\Filament\Clusters\Service\Resources\Bookings\BookingResource;
use App\Filament\Clusters\Service\Resources\Bookings\Tables\BookingsTable;
use App\Repositories\BookingRepository;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingListKtv extends TableWidget
{

    public ?Model $record = null;

    protected int | string | array $columnSpan = 2;

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('admin.ktv.infolist.booking_list');
    }

    public function table(Table $table): Table
    {
        $bookingRepository = app(BookingRepository::class);
        return BookingsTable::configure($table)
            ->recordUrl(fn (Model $record): string => BookingResource::getUrl('view', ['record' => $record]))
            ->defaultPaginationPageOption(5)
            ->query(function () use ($bookingRepository) {
                return $bookingRepository->query()
                    ->with([
                        'ktvUser',
                        'user',
                        'service'
                    ])
                    ->where('ktv_user_id', $this->record?->id)
                    ->withoutGlobalScopes([
                        SoftDeletingScope::class,
                    ]);
            });
    }

}
