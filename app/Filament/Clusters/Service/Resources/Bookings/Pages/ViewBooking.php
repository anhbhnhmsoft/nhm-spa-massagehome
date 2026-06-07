<?php

namespace App\Filament\Clusters\Service\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Filament\Clusters\Service\Resources\Bookings\BookingResource;
use App\Filament\Components\CommonActions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),

            // Chuyển booking sang cho KTV khác
            Action::make('reassign_booking')
                ->visible(function($record) {
                    return in_array($record->status, [
                        BookingStatus::CONFIRMED->value,
                        BookingStatus::OPEN_FOR_APPLICATION->value,
                    ]);
                } )
                ->label(__('admin.booking.actions.reassign.label'))
                ->color('info')
                ->modalHeading(__('admin.booking.actions.reassign.heading'))
                ->modalDescription(__('admin.booking.actions.reassign.description'))
                ->modalSubmitActionLabel(__('admin.booking.actions.reassign.modal_submit'))
                ->modalContent(fn ($record) => view(
                    'filament.clusters.service.bookings.similar_services_table_wrapper',
                    ['record' => $record],
                ))
                ->slideOver()
                ->modalFooterActions(fn () => []),
        ];
    }
}
