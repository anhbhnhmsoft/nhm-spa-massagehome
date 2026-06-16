<?php

namespace App\Filament\Clusters\Service\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Enums\Jobs\WalletTransCase;
use App\Enums\WalletTransactionType;
use App\Filament\Clusters\Service\Resources\Bookings\BookingResource;
use App\Filament\Components\CommonActions;
use App\Jobs\WalletTransactionBookingJob;
use App\Models\WalletTransaction;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

            Action::make('approve_cancel_booking')
                ->visible(fn($record) => $record->status === BookingStatus::WAITING_CANCEL->value)
                ->label(__('admin.booking.actions.confirm_cancel'))
                ->color('danger')
                ->modalHeading(__('admin.booking.status.waiting_cancel'))
                ->modalDescription(__('admin.booking.actions.confirm_cancel_helper_text'))
                ->modalSubmitActionLabel(__('admin.booking.actions.confirm_cancel'))
                ->modalCancelActionLabel(__('admin.common.action.cancel'))
                ->form(function ($record) {
                    $serviceTransaction = WalletTransaction::query()
                        ->where('foreign_key', $record->id)
                        ->where('type', WalletTransactionType::PAYMENT->value)
                        ->first();
                    $transportTransaction = WalletTransaction::query()
                        ->where('foreign_key', $record->id)
                        ->where('type', WalletTransactionType::PAYMENT_FEE_TRANSPORT->value)
                        ->first();
                    $customerPaidTotal = (float) ($serviceTransaction?->point_amount ?? 0) + (float) ($transportTransaction?->point_amount ?? 0);

                    return [
                        Placeholder::make('customer_paid_total')
                            ->label(__('admin.booking.fields.customer_paid_total'))
                            ->content(number_format($customerPaidTotal, 0, '.', ',') . ' ' . __('admin.currency')),
                        TextInput::make('amount_pay_back_to_client')
                            ->label(__('admin.booking.fields.amount_pay_back_to_client'))
                            ->numeric()
                            ->default((string) $customerPaidTotal)
                            ->minValue(0)
                            ->maxValue($customerPaidTotal),
                        Toggle::make('pay_to_ktv')
                            ->label(__('admin.booking.fields.pay_to_ktv'))
                            ->default(false)
                            ->live(),
                        TextInput::make('amount_pay_to_ktv')
                            ->label(__('admin.booking.fields.amount_pay_to_ktv'))
                            ->numeric()
                            ->default('0')
                            ->minValue(0)
                            ->visible(fn (callable $get) => (bool) $get('pay_to_ktv')),
                    ];
                })
                ->action(function ($record, array $data) {
                    $amountPayBackToClient = (float) ($data['amount_pay_back_to_client'] ?? 0);
                    $amountPayToKtv = !empty($data['pay_to_ktv'])
                        ? (float) ($data['amount_pay_to_ktv'] ?? 0)
                        : 0;

                    WalletTransactionBookingJob::dispatch(
                        bookingId: (int) $record->id,
                        case: WalletTransCase::CONFIRM_CANCEL_BOOKING,
                        data: [
                            'amount_pay_back_to_client' => $amountPayBackToClient,
                            'amount_pay_to_ktv' => $amountPayToKtv,
                        ],
                    );

                    Notification::make()
                        ->success()
                        ->title(__('common.success.data_updated'))
                        ->send();
                }),
        ];
    }
}
