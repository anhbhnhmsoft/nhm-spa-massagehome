<?php

namespace App\Filament\Clusters\KTV\Resources\KTVs\Schemas;

use App\Enums\DirectFile;
use App\Enums\Gender;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserFileType;
use App\Enums\UserRole;
use App\Models\Province;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KTVForm
{
    public static function configure(Schema $schema): Schema
    {
        $lang = app()->getLocale();
        return $schema
            ->components([
                Section::make(__('admin.common.table.basic_info'))
                    ->schema([
                        Section::make()
                            ->schema([

                                TextInput::make('name')
                                    ->label(__('admin.common.table.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                        'max'      => __('common.error.max_length', ['max' => 255])
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
                                        'max'      => __('common.error.max_length', ['max' => 255])
                                    ]),
                                TextInput::make('phone')
                                    ->label(__('admin.common.table.phone'))
                                    ->tel()
                                    ->maxLength(20)
                                    ->numeric()
                                    ->required()
                                    ->unique()
                                    ->validationMessages([
                                        'max'      => __('common.error.max_length', ['max' => 20]),
                                        'numeric'  => __('common.error.numeric'),
                                        'max_digits' => __('common.error.max_digits', ['max' => 20]),
                                        'required' => __('common.error.required'),
                                        'unique'   => __('common.error.unique'),
                                    ]),
                            ]),

                        Section::make()
                            ->relationship('profile')
                            ->schema([
                                FileUpload::make('avatar_url')
                                    ->label(__('admin.common.table.avatar'))
                                    ->image()
                                    ->disk('public')
                                    ->directory(DirectFile::KTVA->value)
                                    ->required()
                                    ->downloadable()
                                    ->maxSize(102400)
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),
                                Textarea::make('bio')
                                    ->label(__('admin.common.table.bio'))
                                    ->rows(3),
                                Select::make('gender')
                                    ->label(__('admin.common.table.gender'))
                                    ->options(Gender::toOptions()),
                                DateTimePicker::make('date_of_birth')
                                    ->label(__('admin.common.table.date_of_birth'))
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),
                            ]),

                    ])
                    ->columns(2),

                Section::make(__('admin.ktv_apply.fields.registration_info'))
                    ->relationship('reviewApplication')
                    ->schema([
                        Select::make('status')
                            ->label(__('admin.common.table.status'))
                            ->options(ReviewApplicationStatus::toOptions())
                            ->default(ReviewApplicationStatus::PENDING),
                        Select::make('agency_id')
                            ->label(__('admin.ktv_apply.fields.agency'))
                            ->searchable()
                            ->options(fn() => User::where('role', UserRole::AGENCY->value)->where('is_active', true)->pluck('name', 'id'))
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->columnSpan(1),
                        Select::make('province_code')
                            ->label(__('admin.ktv_apply.fields.province'))
                            ->searchable()
                            ->options(fn() => Province::all()->pluck('name', 'code'))
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->columnSpan(1),

                        Textarea::make('address')
                            ->label(__('admin.ktv_apply.fields.address'))
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->rows(2),

                        TextInput::make('experience')
                            ->label(__('admin.ktv_apply.fields.experience'))
                            ->numeric()
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->suffix(__('admin.ktv_apply.fields.years')),

                        Textarea::make('bio.' . $lang)
                            ->label(__('admin.ktv_apply.fields.experience_desc'))
                            ->rows(3)
                            ->columnSpanFull(),
                        Placeholder::make('effective_date')
                            ->label(__('admin.common.table.effective_date'))
                            ->content(fn($record) => $record?->reviewApplication?->effective_date?->format('d/m/Y H:i:s')),

                        Placeholder::make('application_date')
                            ->label(__('admin.common.table.application_date'))
                            ->content(fn($record) => $record?->reviewApplication?->application_date?->format('d/m/Y H:i:s')),
                    ])
                    ->columns(2),

                Section::make(__('admin.ktv_apply.fields.files'))
                    ->schema([
                        Repeater::make('files')
                            ->label(__('admin.ktv_apply.fields.files'))
                            ->relationship('files')
                            ->columns(3)
                            ->schema([
                                Select::make('type')
                                    ->label(__('admin.ktv_apply.fields.file_type'))
                                    ->options(UserFileType::toOptions())
                                    ->required()
                                    ->columnSpan(1),

                                FileUpload::make('file_path')
                                    ->label('File')
                                    ->directory(DirectFile::KTVA->value)
                                    ->disk('private')
                                    ->required()
                                    ->downloadable()
                                    ->columnSpan(2),
                            ])
                            ->columns(3)
                            ->addable(true)
                            ->deletable(true)
                            ->reorderable(true)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->validationMessages([
                                'min' => __('common.error.min_items', ['min' => 1]),
                                'required' => __('common.error.required'),
                            ]),
                    ]),

                Section::make(__('admin.ktv_apply.fields.system_info'))
                    ->schema([
                        Placeholder::make('created_at')
                            ->label(__('admin.common.table.created_at'))
                            ->content(fn($record) => $record?->created_at?->format('d/m/Y H:i:s')),

                        Placeholder::make('updated_at')
                            ->label(__('admin.common.table.updated_at'))
                            ->content(fn($record) => $record?->updated_at?->format('d/m/Y H:i:s')),
                        Select::make('role')
                            ->label(__('admin.common.table.role'))
                            ->options(UserRole::toOptions())
                            ->default(UserRole::KTV->value)
                            ->disabled(),

                        Toggle::make('is_active')
                            ->label(__('admin.common.table.status'))
                            ->default(true),
                        DateTimePicker::make('last_login_at')
                            ->label(__('admin.common.table.last_login'))
                            ->disabled(),

                    ])
                    ->columns(2),

            ]);
    }
}
