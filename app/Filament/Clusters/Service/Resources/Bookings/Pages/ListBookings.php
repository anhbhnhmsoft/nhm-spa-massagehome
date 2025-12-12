<?php

namespace App\Filament\Clusters\Service\Resources\Bookings\Pages;

use App\Filament\Clusters\Service\Resources\Bookings\BookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make()
            //     ->label(__('admin.common.action.book')),
        ];
    }
}
