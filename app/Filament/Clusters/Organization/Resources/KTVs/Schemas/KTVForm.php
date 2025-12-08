<?php

namespace App\Filament\Clusters\Organization\Resources\KTVs\Schemas;

use App\Enums\Gender;
use App\Enums\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KTVForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.common.table.basic_info'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.common.table.name'))
                            ->required()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => __('common.error.required'),
                                'max'      => __('common.error.max_length', ['max' => 255])
                            ]),
                        FileUpload::make('profile.avatar_url')
                            ->label(__('admin.common.table.avatar'))
                            ->image()
                            ->disk('public')
                            ->required()
                            ->downloadable()
                            ->maxSize(102400)
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ]),
                        // TextInput::make('email')
                        //     ->label(__('admin.common.table.email'))
                        //     ->unique(ignoreRecord: true)
                        //     ->required()
                        //     ->validationMessages([
                        //         'email'     => __('common.error.email'),
                        //         'unique'    => __('common.error.unique')
                        //     ]),
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
                                'max'      => __('common.error.max_length', ['max' => 255])
                            ]),
                        TextInput::make('phone')
                            ->label(__('admin.common.table.phone'))
                            ->tel()
                            ->maxLength(20)
                            ->numeric()
                            ->validationMessages([
                                'max'      => __('common.error.max_length', ['max' => 20]),
                                'numeric'  => __('common.error.numeric'),
                                'max_digits' => __('common.error.max_digits', ['max' => 20])
                            ]),
                        Textarea::make('profile.bio')
                            ->rows(3),
                        Select::make('profile.gender')
                            ->label(__('admin.common.table.gender'))
                            ->options(Gender::toOptions()),
                        DateTimePicker::make('profile.date_of_birth')
                            ->label(__('admin.common.table.date_of_birth'))
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ]),
                    ])
                    ->columns(2),

                Section::make(__('admin.common.table.account_info'))
                    ->schema([

                        Select::make('role')
                            ->label(__('admin.common.table.role'))
                            ->options(UserRole::toOptions())
                            ->disabled(),

                        Toggle::make('is_active')
                            ->label(__('admin.common.table.status'))
                            ->default(true)
                            ->hidden(fn($livewire) => $livewire instanceof CreateRecord),
                        DateTimePicker::make('last_login_at')
                            ->label(__('admin.common.table.last_login'))
                            ->disabled()
                            ->hidden(fn($livewire) => $livewire instanceof CreateRecord),
                    ])
                    ->columns(2),

            ]);
    }
}
