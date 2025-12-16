<?php

namespace App\Filament\Clusters\Agency\Resources\Agencies\Schemas;

use App\Enums\DirectFile;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserFileType;
use App\Models\Province;
use App\Services\LocationService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
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

class AgencyForm
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
                        Placeholder::make('created_at')
                            ->label(__('admin.common.table.created_at'))
                            ->content(fn($record) => $record?->created_at?->format('d/m/Y H:i:s')),

                        Placeholder::make('updated_at')
                            ->label(__('admin.common.table.updated_at'))
                            ->content(fn($record) => $record?->updated_at?->format('d/m/Y H:i:s')),

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
                            ])
                            ->columnSpan('full'),
                    ])
                    ->columns(2),
                Section::make(__('admin.agency_apply.fields.registration_info'))
                    ->relationship(name: 'reviewApplication')
                    ->schema([

                        Select::make('search_location')
                            ->label(__('admin.ktv_apply.fields.search_address'))
                            ->searchable()
                            ->live(debounce: 500)
                            ->getSearchResultsUsing(function (string $search) {
                                if (!$search) return [];
                                $service = app(LocationService::class);
                                $res = $service->autoComplete($search);
                                if (!$res->isSuccess()) return [];
                                return collect($res->getData())->pluck('formatted_address', 'place_id')->toArray();
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                if (!$value) return null;

                                $service = app(LocationService::class);
                                $res = $service->getDetail($value);

                                return $res->isSuccess()
                                    ? $res->getData()['formatted_address']
                                    : null;
                            })
                            ->afterStateUpdated(function ($set, ?string $state) {
                                if (!$state) return;
                                $service = app(LocationService::class);
                                $res = $service->getDetail($state);
                                if ($res->isSuccess()) {
                                    $data = $res->getData();
                                    $set('address', $data['formatted_address']);
                                    $set('latitude', $data['latitude']);
                                    $set('longitude', $data['longitude']);
                                }
                            })
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Hidden::make('latitude'),
                        Hidden::make('longitude'),

                        Textarea::make('address')
                            ->label(__('admin.ktv_apply.fields.address'))
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('bio.' . $lang)
                            ->label(__('admin.agency_apply.fields.bio'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Hidden::make('status')
                            ->default(ReviewApplicationStatus::APPROVED),
                        TextInput::make('note')
                            ->label(__('admin.agency_apply.fields.note')),

                    ])
                    ->columns(2),
            ]);
    }
}
