<?php

namespace App\Filament\Clusters\Service\Resources\Bookings\Pages;

use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\Service\Resources\Bookings\BookingResource;
use App\Filament\Components\CommonActions;
use App\Services\BookingService;
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
                            TextInput::make('price')
                                ->label(__('admin.booking.fields.price'))
                                ->prefix(__('admin.currency'))
                                ->disabled()
                                ->formatStateUsing(fn() => number_format($record->price, 0, '.', ',')),

                            TextInput::make('amount_pay_back_to_client')
                                ->label(__('admin.booking.fields.amount_pay_back_to_client'))
                                ->prefix(__('admin.currency'))
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($get, $set, ?string $state) use ($record) {
                                    $price = (float)$record->price;
                                    $amountClient = (float)$state;
                                    $amountKtv = round($price - $amountClient);
                                    $set('amount_pay_to_ktv', $amountKtv);
                                }),

                            TextInput::make('amount_pay_to_ktv')
                                ->label(__('admin.booking.fields.amount_pay_to_ktv'))
                                ->prefix(__('admin.currency'))
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($get, $set, ?string $state) use ($record) {
                                    $price = (float)$record->price;
                                    $amountKtv = (float)$state;
                                    $amountClient = round($price - $amountKtv);
                                    $set('amount_pay_back_to_client', $amountClient);
                                }),
                            TextInput::make('cancel_by')
                                ->disabled()
                                ->label(__('admin.booking.fields.cancel_by'))
                                ->dehydrated()
                                ->formatStateUsing(fn() => UserRole::getLabel($record->cancel_by)),
                            Textarea::make('reason_cancel')
                                ->label(__('admin.booking.fields.reason_cancel'))
                                ->disabled()
                                ->dehydrated()
                                ->default($record->reason_cancel),

                        ])
                ])
                ->modalHeading(__('admin.booking.actions.cancel.heading'))
                ->modalDescription(__('admin.booking.actions.cancel.description'))
                ->action(function (BookingService $bookingService, $record, $data) {
                    $result = $bookingService->approveCancel($record, $data);
                    if ($result->isSuccess()) {
                        Notification::make()
                            ->title(__('admin.booking.actions.cancel_success_title'))
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('admin.booking.actions.cancel_error_title'))
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
