<?php

namespace App\Filament\Clusters\KTV\Resources\KTVApplies\Schemas;

use App\Enums\Gender;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserFileType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KTVApplyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ktv_apply.fields.personal_info'))
                    ->schema([
                        FileUpload::make('profile.avatar_url')
                            ->label(__('admin.common.table.avatar'))
                            ->image()
                            ->disk('public')
                            ->image()
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->downloadable(),

                        TextInput::make('name')
                            ->label(__('admin.common.table.name'))
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
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
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'max'      => __('common.error.max_length', ['max' => 20]),
                                'numeric'  => __('common.error.numeric'),
                                'max_digits' => __('common.error.max_digits', ['max' => 20]),
                                'required' => __('common.error.required'),
                                'unique'   => __('common.error.unique'),
                            ])
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord),
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
                            ])
                            ->hidden(fn($livewire) => $livewire instanceof ViewRecord),
                        Select::make('profile.gender')
                            ->label(__('admin.common.table.gender'))
                            ->options(Gender::toOptions())
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord),

                        DatePicker::make('profile.date_of_birth')
                            ->label(__('admin.common.table.date_of_birth'))
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord),

                        Textarea::make('profile.bio')
                            ->label(__('admin.ktv_apply.fields.bio'))
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->rows(3),
                    ])
                    ->columns(2),

                Section::make(__('admin.ktv_apply.fields.registration_info'))
                    ->schema([
                        Select::make('reviewApplication.status')
                            ->label(__('admin.common.table.status'))
                            ->options(ReviewApplicationStatus::toOptions())
                            ->default(ReviewApplicationStatus::PENDING)
                            ->hidden(fn($livewire) => $livewire instanceof CreateRecord)
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord),

                        TextInput::make('reviewApplication.province.name')
                            ->label(__('admin.ktv_apply.fields.province'))
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord),

                        Textarea::make('reviewApplication.address')
                            ->label(__('admin.ktv_apply.fields.address'))
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->rows(2),

                        TextInput::make('reviewApplication.experience')
                            ->label(__('admin.ktv_apply.fields.experience'))
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->suffix(__('admin.ktv_apply.fields.years')),

                        TagsInput::make('reviewApplication.skills')
                            ->label(__('admin.ktv_apply.fields.skills'))
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord),

                        Textarea::make('reviewApplication.bio')
                            ->label(__('admin.ktv_apply.fields.experience_desc'))
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('admin.ktv_apply.fields.files'))
                    ->schema([
                        Repeater::make('files')
                            ->label(__('admin.ktv_apply.fields.files'))
                            ->relationship('files')
                            ->schema([
                                Select::make('type')
                                    ->label(__('admin.ktv_apply.fields.file_type'))
                                    ->options(UserFileType::toOptions())
                                    ->required()
                                    ->disabled(fn($livewire) => $livewire instanceof ViewRecord),

                                FileUpload::make('file_path')
                                    ->label('File')
                                    ->disk('public')
                                    ->required()
                                    ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                                    ->downloadable(),
                            ])
                            ->columns(3)
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->addable(fn($livewire) => $livewire instanceof CreateRecord)
                            ->deletable(fn($livewire) => $livewire instanceof CreateRecord)
                            ->reorderable(fn($livewire) => $livewire instanceof CreateRecord)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->validationMessages([
                                'min_items' => __('common.error.min_items', ['min' => 1]),
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
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
