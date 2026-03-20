<?php

namespace App\Filament\Clusters\User\Resources\Customers\Schemas;

use App\Enums\Admin\AdminGate;
use App\Enums\DirectFile;
use App\Enums\Gender;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->disabled(fn(): bool => ! Gate::allows(AdminGate::ALLOW_ADMIN))
            ->components([
                // Thông tin cơ bản
                Section::make(__('admin.common.table.basic_info'))
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('id')
                                    ->label(__('admin.common.table.id'))
                                    ->hiddenOn("create")
                                    ->disabled(),
                                TextInput::make('phone')
                                    ->label(__('admin.common.table.phone'))
                                    ->tel()
                                    ->maxLength(20)
                                    ->disabled(),
                                TextInput::make('email')
                                    ->label(__('admin.common.table.email'))
                                    ->email()
                                    ->maxLength(255)
                                    ->disabled(),
                                TextInput::make('name')
                                    ->label(__('admin.common.table.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                        'max' => __('common.error.max_length', ['max' => 255])
                                    ]),
                                TextInput::make('password')
                                    ->label(__('admin.common.table.password'))
                                    ->password()
                                    ->required(fn($livewire) => $livewire instanceof CreateRecord)
                                    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                                    ->dehydrated(fn($state) => filled($state))
                                    ->revealable()
                                    ->maxLength(255)
                                    ->helperText(__('admin.common.table.password_desc'))
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                        'max' => __('common.error.max_length', ['max' => 255])
                                    ]),
                                Toggle::make('is_active')
                                    ->label(__('admin.common.table.status'))
                                    ->columnSpanFull()
                                    ->default(true),
                            ]),

                        Section::make()
                            ->relationship('profile')
                            ->schema([
                                FileUpload::make('avatar_url')
                                    ->label(__('admin.common.table.avatar'))
                                    ->image()
                                    ->avatar()
                                    ->imageEditor()
                                    ->disk('public')
                                    ->directory(DirectFile::KTVA->value)
                                    ->required()
                                    ->downloadable()
                                    ->alignCenter()
                                    ->maxSize(102400)
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),
                                Textarea::make('bio')
                                    ->label(__('admin.common.table.bio'))
                                    ->rows(3),
                                Select::make('gender')
                                    ->label(__('admin.common.table.gender'))
                                    ->options(Gender::toOptions())
                                    ->required()
                                    ->placeholder(__('common.placeholder.type'))
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),
                                DatePicker::make('date_of_birth')
                                    ->label(__('admin.common.table.date_of_birth'))
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),
                            ]),

                    ])
                    ->compact()
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
