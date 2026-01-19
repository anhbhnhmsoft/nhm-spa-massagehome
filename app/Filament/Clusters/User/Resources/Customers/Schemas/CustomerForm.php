<?php

namespace App\Filament\Clusters\User\Resources\Customers\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        Section::make(__('admin.customer.section.info'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('admin.customer.fields.name'))
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),
                                TextInput::make('phone')
                                    ->label(__('admin.customer.fields.phone'))
                                    ->tel()
                                    ->required()
                                    ->unique()
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                        'unique' => __('common.error.unique'),
                                    ]),
                                TextInput::make('password')
                                    ->label(__('admin.common.table.password'))
                                    ->password()
                                    ->required(fn($livewire) => $livewire instanceof CreateRecord)
                                    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                                    ->dehydrated(fn($state) => filled($state))
                                    ->revealable()
                                    ->maxLength(255)
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                        'max' => __('common.error.max_length', ['max' => 255])
                                    ]),
                                Section::make()
                                    ->relationship('profile')
                                    ->schema([
                                        DatePicker::make('date_of_birth')
                                            ->label(__('admin.customer.fields.dob')),
                                    ]),
                                Select::make('role')
                                    ->label(__('admin.customer.fields.role'))
                                    ->options(UserRole::class)
                                    ->default(UserRole::CUSTOMER)
                                    ->disabled()
                                    ->dehydrated()
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),
                            ])->columns(2),

                        Section::make('Wallet')
                            ->label(__('admin.customer.section.wallet'))
                            ->relationship('wallet')
                            ->schema([
                                TextInput::make('balance')
                                    ->label(__('admin.customer.fields.balance'))
                                    ->numeric()
                                    ->disabled()
                                    ->suffix(__('admin.currency'))
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),
                            ]),

                        Section::make(__('admin.customer.section.booking_history'))
                            ->label(__('admin.customer.section.booking_history'))
                            ->schema([
                                Repeater::make('bookings')
                                    ->relationship('bookings')
                                    ->label(__('admin.customer.section.booking_history'))
                                    ->schema([
                                        Select::make('service_id')
                                            ->relationship('service', 'name')
                                            ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                                            ->label(__('admin.booking.fields.service')),
                                        DateTimePicker::make('booking_time')
                                            ->label(__('admin.booking.fields.booking_time')),
                                        TextInput::make('price')
                                            ->label(__('admin.booking.fields.price'))
                                            ->numeric()
                                            ->validationMessages([
                                                'required' => __('common.error.required'),
                                            ]),
                                        TextInput::make('status')
                                            ->label(__('admin.booking.fields.status'))
                                            ->formatStateUsing(fn($state) => \App\Enums\BookingStatus::tryFrom($state)?->label()),
                                    ])
                                    ->columns(2)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->disabled()
                            ])
                            ->visible(fn($record) => $record !== null),
                        Section::make(__('admin.customer.section.reviews'))
                            ->label(__('admin.customer.section.reviews'))
                            ->schema([
                                Repeater::make('reviews')
                                    ->relationship('reviewWrited')
                                    ->label(__('admin.customer.section.reviews'))
                                    ->schema([
                                        Grid::make()
                                            ->schema([
                                                Select::make('service_id')
                                                    ->relationship('service', 'name')
                                                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                                                    ->label(__('admin.booking.fields.service')),
                                                Select::make('user_id')
                                                    ->relationship('recipient', 'name')
                                                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                                                    ->label(__('admin.booking.fields.user')),
                                                TextInput::make('rating')
                                                    ->label(__('admin.booking.fields.rating')),
                                                TextInput::make('comment')
                                                    ->label(__('admin.booking.fields.comment')),
                                            ])
                                            ->columns(2)
                                    ])
                                    ->columns(2)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->disabled()
                            ])
                            ->visible(fn($record) => $record !== null),
                    ])
                    ->columnSpan('full')
            ]);
    }
}
