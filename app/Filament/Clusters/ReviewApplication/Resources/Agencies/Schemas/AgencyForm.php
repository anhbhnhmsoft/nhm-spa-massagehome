<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\Agencies\Schemas;

use App\Core\Helper;
use App\Enums\DirectFile;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Models\Province;
use App\Services\LocationService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class AgencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->disabled(function ($record) {
                if (!$record) {
                    return false;
                }
                $status = $record->reviewApplication?->status;

                return in_array($status, [
                    ReviewApplicationStatus::PENDING,
                    ReviewApplicationStatus::REJECTED,
                ]);
            })
            ->components([
                // Thông tin cơ bản
                Section::make(__('admin.common.table.basic_info'))
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('id')
                                    ->label(__('admin.common.table.id'))
                                    ->disabled(),
                                TextInput::make('name')
                                    ->label(__('admin.common.table.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                        'max' => __('common.error.max_length', ['max' => 255])
                                    ]),
                                TextInput::make('phone')
                                    ->label(__('admin.common.table.phone'))
                                    ->tel()
                                    ->maxLength(20)
                                    ->required()
                                    ->unique()
                                    ->disabled()
                                    ->validationMessages([
                                        'max' => __('common.error.max_length', ['max' => 20]),
                                        'max_digits' => __('common.error.max_digits', ['max' => 20]),
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
                    ->columns(2),

                // Thông tin đăng ký
                Section::make(__('admin.agency_apply.fields.registration_info'))
                    ->relationship('reviewApplication')
                    ->compact()
                    ->afterHeader([
                        Text::make(function($record) {
                            return __('admin.common.table.status_review') . ": " . $record->status->label();
                        })
                            ->badge()
                            ->color(fn($record) => $record->status?->color()),
                    ])
                    ->schema([
                        Hidden::make('role')
                            ->default(UserRole::AGENCY->value)
                            ->dehydrateStateUsing(fn() => UserRole::AGENCY->value)
                            ->dehydrated(true),
                        Hidden::make('status')
                            ->label(__('admin.common.table.status'))
                            ->default(ReviewApplicationStatus::APPROVED),

                        Textarea::make('bio.' . Language::VIETNAMESE->value)
                            ->label(__('admin.agency_apply.fields.bio_vi'))
                            ->rows(3)
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->columnSpanFull(),
                        Textarea::make('bio.' . Language::ENGLISH->value)
                            ->label(__('admin.agency_apply.fields.bio_en'))
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('bio.' . Language::CHINESE->value)
                            ->label(__('admin.agency_apply.fields.bio_cn'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Thông tin địa điểm
                Section::make(__('admin.agency_apply.fields.location_info'))
                    ->description(__('admin.agency_apply.fields.location_info_desc'))
                    ->relationship('reviewApplication')
                    ->aside()
                    ->columnSpanFull()
                    ->compact()
                    ->schema([
                        Select::make('province_code')
                            ->label(__('admin.agency_apply.fields.province'))
                            ->searchable()
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->options(fn() => Province::all()->pluck('name', 'code'))
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->columnSpan(1),

                        Select::make('search_location')
                            ->label(__('admin.agency_apply.fields.address_search'))
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
                                    : (string) $value;
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

                        TextInput::make('latitude')
                            ->label(__('admin.agency_apply.fields.latitude') ?? 'Latitude')
                            ->numeric()
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->rules(['nullable', 'numeric', 'between:-90,90'])
                            ->columnSpan(1),

                        TextInput::make('longitude')
                            ->label(__('admin.agency_apply.fields.longitude') ?? 'Longitude')
                            ->numeric()
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->rules(['nullable', 'numeric', 'between:-180,180'])
                            ->columnSpan(1),

                        Textarea::make('address')
                            ->label(__('admin.agency_apply.fields.address'))
                            ->columnSpanFull()
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->rows(2),
                    ]),

                // Thông tin CCCD
                Grid::make()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Section::make(__('admin.agency_apply.fields.identity_card_front'))
                            ->compact()
                            ->schema([
                                FileUpload::make('cccd_front_path')
                                    ->hiddenLabel()
                                    ->directory(fn($record) => DirectFile::makePathById(DirectFile::AGENCY, $record?->id ?? Helper::getTimestampAsId()))
                                    ->disk('private')
                                    ->required()
                                    ->image()
                                    ->maxSize(102400)
                                    ->downloadable()
                                    ->columnSpanFull()
                                    ->afterStateHydrated(fn($component, $record) => $component->state($record?->cccdFront()->first()?->file_path)),
                            ]),

                        Section::make(__('admin.agency_apply.fields.identity_card_back'))
                            ->compact()
                            ->schema([
                                FileUpload::make('cccd_back_path')
                                    ->hiddenLabel()
                                    ->directory(fn($record) => DirectFile::makePathById(DirectFile::AGENCY, $record?->id ?? Helper::getTimestampAsId()))
                                    ->disk('private')
                                    ->required()
                                    ->image()
                                    ->maxSize(102400)
                                    ->downloadable()
                                    ->columnSpanFull()
                                    ->afterStateHydrated(fn($component, $record) => $component->state($record?->cccdBack()->first()?->file_path)),
                            ]),

                        Section::make(__('admin.agency_apply.fields.face_with_identity_card'))
                            ->compact()
                            ->schema([
                                FileUpload::make('face_with_identity_card_path')
                                    ->hiddenLabel()
                                    ->directory(fn($record) => DirectFile::makePathById(DirectFile::AGENCY, $record?->id ?? Helper::getTimestampAsId()))
                                    ->disk('private')
                                    ->required()
                                    ->image()
                                    ->maxSize(102400)
                                    ->downloadable()
                                    ->columnSpanFull()
                                    ->deletable()
                                    ->afterStateHydrated(fn($component, $record) => $component->state($record?->faceWithIdentityCard()->first()?->file_path)),
                            ]),
                    ]),
            ]);
    }
}
