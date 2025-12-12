<?php

namespace App\Filament\Clusters\Service\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Enums\PaymentType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->label(__('admin.booking.fields.user'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('service_id')
                                    ->relationship('service', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                                    ->label(__('admin.booking.fields.service'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn($state, callable $set) => $set('price', \App\Models\ServiceOption::where('service_id', $state)->first()?->price ?? 0)), // Simple auto-fill logic
                                Select::make('coupon_id')
                                    ->relationship('coupon', 'code')
                                    ->label(__('admin.booking.fields.coupon'))
                                    ->searchable()
                                    ->preload(),
                                DateTimePicker::make('booking_time')
                                    ->label(__('admin.booking.fields.booking_time'))
                                    ->required()
                                    ->seconds(false),
                                TextInput::make('duration')
                                    ->label(__('admin.booking.fields.duration'))
                                    ->numeric()
                                    ->suffix('minutes')
                                    ->required(),
                                Select::make('status')
                                    ->options(BookingStatus::toOptions())
                                    ->label(__('admin.booking.fields.status'))
                                    ->required()
                                    ->default(BookingStatus::PENDING->value),
                                Select::make('payment_type')
                                    ->options(PaymentType::toOptions())
                                    ->label(__('admin.booking.fields.payment_type')),
                                TextInput::make('price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->label(__('admin.booking.fields.price'))
                                    ->required(),
                                TextInput::make('price_before_discount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->label(__('admin.booking.fields.price_before_discount')),
                            ]),
                        Section::make(__('admin.booking.fields.address'))
                            ->schema([
                                Textarea::make('address')
                                    ->label(__('admin.booking.fields.address'))
                                    ->rows(2),
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('latitude')
                                            ->label(__('admin.booking.fields.latitude'))
                                            ->numeric(),
                                        TextInput::make('longitude')
                                            ->label(__('admin.booking.fields.longitude'))
                                            ->numeric(),
                                    ]),
                            ]),
                        Textarea::make('note')
                            ->label(__('admin.booking.fields.note'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
            ]);
    }
}
