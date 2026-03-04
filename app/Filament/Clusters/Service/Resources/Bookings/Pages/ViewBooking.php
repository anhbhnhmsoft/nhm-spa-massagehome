<?php

namespace App\Filament\Clusters\Service\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Enums\Jobs\WalletTransCase;
use App\Enums\UserRole;
use App\Filament\Clusters\Service\Resources\Bookings\BookingResource;
use App\Filament\Components\CommonActions;
use App\Jobs\WalletTransactionBookingJob;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;

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
                    return $record->status === BookingStatus::WAITING_CANCEL->value || $record->status === BookingStatus::CONFIRMED->value;
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

            // Xác nhận hủy booking
            Action::make('confirm_cancel')
                ->hidden(fn($record) => $record->status !== BookingStatus::WAITING_CANCEL->value)
                ->label(__('admin.booking.actions.confirm_cancel'))
                ->color('success')
                ->icon('heroicon-m-check-circle')
                ->requiresConfirmation()
                ->schema(fn($record) => [
                    Section::make()
                        ->columns(1)
                        ->compact()
                        ->description(__('admin.booking.actions.confirm_cancel_helper_text'))
                        ->schema([
                            TextInput::make('amount_pay_back_to_client')
                                ->label(__('admin.booking.fields.amount_pay_back_to_client'))
                                ->prefix(__('admin.currency'))
                                ->numeric(),
                            TextInput::make('amount_pay_to_ktv')
                                ->label(__('admin.booking.fields.amount_pay_to_ktv'))
                                ->prefix(__('admin.currency'))
                                ->numeric(),
                        ])
                ])
                ->modalHeading(__('admin.booking.actions.cancel.heading'))
                ->modalDescription(__('admin.booking.actions.cancel.description'))
                ->action(function ($record, $data) {
                    WalletTransactionBookingJob::dispatch(
                        bookingId: $record->id,
                        data: [
                            'amount_pay_back_to_client' => (int)($data['amount_pay_back_to_client'] ?? 0),
                            'amount_pay_to_ktv' => (int)($data['amount_pay_to_ktv'] ?? 0),
                        ],
                        case: WalletTransCase::CONFIRM_CANCEL_BOOKING,
                    );
                    Notification::make()
                        ->title(__('admin.booking.actions.cancel_processing_title'))
                        ->success()
                        ->send();
                })
                ->modalFooterActions(function ($action) {
                    return [
                        $action->getModalCancelAction()
                            ->label(__('common.action.close'))
                            ->color('danger'),
                        $action->getModalSubmitAction()
                            ->label(__('common.action.submit'))
                            ->color('success'),
                    ];
                }),
        ];
    }
}
