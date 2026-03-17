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

/**
 * Lấy danh sách booking của KTV
 */
class BookingListTableWidget extends TableWidget
{

    protected static bool $isLazy = true;

    public ?Model $record = null;

    protected int | string | array $columnSpan = 1;

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
            ->filters([])
            ->query(function () use ($bookingRepository) {
                return $bookingRepository->query()
                    ->with([
                        'ktvUser',
                        'user',
                        'service'
                    ])
                    ->where('ktv_user_id', $this->record?->id);
            });
    }

}
