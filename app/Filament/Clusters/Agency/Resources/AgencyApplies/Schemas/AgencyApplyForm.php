<?php

namespace App\Filament\Clusters\Agency\Resources\AgencyApplies\Schemas;

use App\Enums\DirectFile;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserFileType;
use App\Models\Province;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\App;

class AgencyApplyForm
{
    public static function configure(Schema $schema): Schema
    {
        $lang = App::getLocale();
        return $schema
            ->components([
                Section::make(__('admin.agency_apply.fields.personal_info'))
                    ->schema([

                        TextInput::make('name')
                            ->label(__('admin.common.table.name'))
                            ->required()
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
                            ->unique()
                            ->required()
                            ->validationMessages([
                                'max'      => __('common.error.max_length', ['max' => 20]),
                                'numeric'  => __('common.error.numeric'),
                                'max_digits' => __('common.error.max_digits', ['max' => 20]),
                                'required' => __('common.error.required'),
                                'unique'   => __('common.error.unique'),
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
                            ])
                            ->hidden(fn($livewire) => $livewire instanceof ViewRecord),
                    ])
                    ->columns(2),
                Section::make(__('admin.agency_apply.fields.registration_info'))
                    ->relationship(name: 'reviewApplication')
                    ->schema([

                        Select::make('province_code')
                            ->required()
                            ->label(__('admin.agency_apply.fields.province'))
                            ->searchable()
                            ->options(fn() => Province::all()->pluck('name', 'code'))
                            ->columnSpan(1)
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ]),

                        Textarea::make('address')
                            ->required()
                            ->label(__('admin.agency_apply.fields.address'))
                            ->rows(2)
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ]),
                        Textarea::make('bio.' . $lang)
                            ->label(__('admin.agency_apply.fields.bio'))
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ]),
                        Select::make('status')
                            ->label(__('admin.agency_apply.fields.status'))
                            ->options(ReviewApplicationStatus::toOptions())
                            ->required()
                            ->default(ReviewApplicationStatus::PENDING)
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ]),

                    ])
                    ->columns(2),

                Section::make(__('admin.agency_apply.fields.files'))
                    ->schema([
                        Repeater::make('files')
                            ->label(__('admin.agency_apply.fields.files'))
                            ->relationship(name: 'files')
                            ->columns(3)
                            ->schema([
                                Select::make('type')
                                    ->label(__('admin.agency_apply.fields.file_type'))
                                    ->options(UserFileType::toOptions())
                                    ->required()
                                    ->columnSpan(1),

                                FileUpload::make('file_path')
                                    ->label('File')
                                    ->directory(DirectFile::AGENCY->value)
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

                Section::make(__('admin.agency_apply.fields.system_info'))
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
