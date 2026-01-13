<?php

namespace App\Filament\Clusters\Service\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Enums\PaymentType;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('admin.booking.fields.user'))
                    ->searchable(),
                TextColumn::make('service.name')
                    ->label(__('admin.booking.fields.service'))
                    ->searchable(),
                TextColumn::make('duration')
                    ->label(__('admin.booking.fields.duration')),
                TextColumn::make('booking_time')
                    ->label(__('admin.booking.fields.booking_time')),
                TextColumn::make('start_time')
                    ->label(__('admin.booking.fields.start_time')),
                TextColumn::make('end_time')
                    ->label(__('admin.booking.fields.end_time')),
                TextColumn::make('price')
                    ->label(__('admin.booking.fields.price'))
                    ->searchable(),
                TextColumn::make('price_before_discount')
                    ->label(__('admin.booking.fields.price_before_discount'))
                    ->searchable(),
                TextColumn::make('coupon.name')
                    ->label(__('admin.booking.fields.coupon'))
                    ->searchable(),
                TextColumn::make('payment_type')
                    ->label(__('admin.booking.fields.payment_type'))
                    ->formatStateUsing(fn($state) => PaymentType::getLabel($state)),
                TextColumn::make('note')
                    ->label(__('admin.booking.fields.note'))
                    ->searchable()
                    ->limit(50),
                TextColumn::make('address')
                    ->label(__('admin.booking.fields.address'))
                    ->searchable(),
                // TextColumn::make('latitude')
                //     ->label(__('admin.booking.fields.latitude')),
                // TextColumn::make('longitude')
                //     ->label(__('admin.booking.fields.longitude')),
                TextColumn::make('status')
                    ->label(__('admin.booking.fields.status'))
                    ->formatStateUsing(fn($state) => BookingStatus::getLabel($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.booking.fields.status'))
                    ->options(BookingStatus::toOptions()),
                SelectFilter::make('payment_type')
                    ->label(__('admin.booking.fields.payment_type'))
                    ->options(PaymentType::toOptions()),
            ])
            ->recordActions(
                actions: [
                    ActionGroup::make([
                        Action::make('view')
                            ->label(__('admin.booking.actions.view.label'))
                            ->icon('heroicon-o-eye')
                            ->modalHeading(__('admin.booking.actions.view.heading'))
                            ->modalDescription(__('admin.booking.actions.view.description'))
                            ->fillForm(fn($record) => $record->toArray())
                            ->schema([
                                Section::make()
                                    ->columnSpanFull()
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('user.name')
                                                    ->label(__('admin.booking.fields.user'))
                                                    ->formatStateUsing(fn($record) => $record->user->name),
                                                TextInput::make('service.name')
                                                    ->label(__('admin.booking.fields.service'))
                                                    ->formatStateUsing(fn($record) => $record->service->name),
                                                TextInput::make('status')
                                                    ->label(__('admin.booking.fields.status'))
                                                    ->formatStateUsing(fn($record) => BookingStatus::tryFrom($record->status)?->label()),
                                                TextInput::make('booking_time')
                                                    ->label(__('admin.booking.fields.booking_time')),
                                                TextInput::make('duration')
                                                    ->label(__('admin.booking.fields.duration'))
                                                    ->suffix('minutes'),
                                            ]),
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('price')
                                                    ->label(__('admin.booking.fields.price'))
                                                    ->prefix('$'),
                                                TextInput::make('payment_type')
                                                    ->label(__('admin.booking.fields.payment_type'))
                                                    ->formatStateUsing(fn($record) => PaymentType::tryFrom($record->payment_type)?->label()),
                                            ]),
                                        Textarea::make('address')
                                            ->label(__('admin.booking.fields.address'))
                                            ->columnSpanFull(),
                                        Textarea::make('note')
                                            ->label(__('admin.booking.fields.note'))
                                            ->columnSpanFull(),
                                    ])
                            ])
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel(__('admin.common.action.close')),

                        // Action::make('view_location')
                        //     ->label(__('admin.booking.actions.view_location'))
                        //     ->icon('heroicon-o-map-pin')
                        //     ->url(fn($record) => "https://www.google.com/maps/search/?api=1&query={$record->latitude},{$record->longitude}")
                        //     ->openUrlInNewTab()
                        //     ->visible(fn($record) => $record->latitude && $record->longitude),
                    ]),
                ],
                position: RecordActionsPosition::BeforeCells
            )
            ->poll('2s');
    }
}
