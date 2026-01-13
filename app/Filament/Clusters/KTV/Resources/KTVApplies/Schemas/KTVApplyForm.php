<?php

namespace App\Filament\Clusters\KTV\Resources\KTVApplies\Schemas;

use App\Core\Helper;
use App\Enums\DirectFile;
use App\Enums\Gender;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserFileType;
use App\Enums\UserRole;
use App\Models\Province;
use App\Models\User;
use App\Services\LocationService;
use Filament\Forms\Components\DatePicker;
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

class KTVApplyForm
{
    public static function configure(Schema $schema): Schema
    {
        $lang = app()->getLocale();
        return $schema
            ->components([
                Section::make(__('admin.ktv_apply.fields.personal_info'))
                    ->schema([
                        Section::make([
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
                                ->validationMessages([
                                    'required' => __('common.error.required'),
                                    'max' => __('common.error.max_length', ['max' => 255])
                                ])
                                ->hidden(fn($livewire) => $livewire instanceof ViewRecord),
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
                                    ->image()
                                    ->downloadable()
                                    ->maxSize(102400)
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),
                                Select::make('gender')
                                    ->label(__('admin.common.table.gender'))
                                    ->options(Gender::toOptions()),

                                DatePicker::make('date_of_birth')
                                    ->label(__('admin.common.table.date_of_birth')),

                                Textarea::make('bio')
                                    ->label(__('admin.ktv_apply.fields.bio'))
                                    ->rows(3),
                            ])
                    ])
                    ->columns(2),
                Section::make(__('admin.ktv_apply.fields.registration_info'))
                    ->relationship('getStaffReviewsAttribute')
                    ->schema([
                        Select::make('status')
                            ->label(__('admin.common.table.status'))
                            ->options(ReviewApplicationStatus::toOptions())
                            ->default(ReviewApplicationStatus::PENDING),
                        Select::make('referrer_id')
                            ->label(__('admin.ktv_apply.fields.agency'))
                            ->relationship(
                                name: 'referrer', // Tên function quan hệ trong Model
                                titleAttribute: 'name', // Cột dùng để hiển thị và tìm kiếm
                                modifyQueryUsing: fn ($query) => $query
                                    ->whereIn('role', [UserRole::AGENCY->value, UserRole::KTV->value])
                                    ->where('is_active', true)
                            )
                            ->searchable() // Filament sẽ tự động search theo titleAttribute (name)
                            ->preload() // Load trước một ít dữ liệu để chọn nhanh
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->columnSpan(1),
                        Select::make('province_code')
                            ->label(__('admin.ktv_apply.fields.province'))
                            ->searchable()
                            ->options(fn() => Province::all()->pluck('name', 'code'))
                            ->columnSpan(1),

                        Select::make('search_location')
                            ->label(__('admin.ktv_apply.fields.address_search'))
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
                            ->columnSpanFull()
                            ->rows(2),

                        TextInput::make('experience')
                            ->label(__('admin.ktv_apply.fields.experience'))
                            ->numeric()
                            ->suffix(__('admin.ktv_apply.fields.years')),

                        Textarea::make('bio.' . $lang)
                            ->label(__('admin.ktv_apply.fields.experience_desc'))
                            ->rows(3)
                            ->columnSpanFull(),
                        Hidden::make('role')
                            ->default(UserRole::KTV->value),
                    ])
                    ->columns(2),

                Section::make(__('admin.ktv_apply.fields.files'))
                    ->schema([
                        Section::make(__('admin.ktv_apply.file_type.identity_card_front'))
                            ->schema([
                                FileUpload::make('cccd_front_path')
                                    ->label(__('admin.ktv_apply.file_type.identity_card_front'))
                                    ->directory(fn($record) => DirectFile::makePathById(DirectFile::KTVA, $record?->id ?? Helper::getTimestampAsId()))
                                    ->disk('private')
                                    ->required()
                                    ->image()
                                    ->maxSize(102400)
                                    ->downloadable()
                                    ->columnSpanFull()
                                    ->afterStateHydrated(fn($component, $record) => $component->state($record?->cccdFront()->first()?->file_path)),
                            ])->columnSpan(1),

                        Section::make(__('admin.ktv_apply.file_type.identity_card_back'))
                            ->schema([
                                FileUpload::make('cccd_back_path')
                                    ->label(__('admin.ktv_apply.file_type.identity_card_back'))
                                    ->directory(fn($record) => DirectFile::makePathById(DirectFile::KTVA, $record?->id ?? Helper::getTimestampAsId()))
                                    ->disk('private')
                                    ->required()
                                    ->image()
                                    ->maxSize(102400)
                                    ->downloadable()
                                    ->columnSpanFull()
                                    ->afterStateHydrated(fn($component, $record) => $component->state($record?->cccdBack()->first()?->file_path)),
                            ])->columnSpan(1),

                        Section::make(__('admin.ktv_apply.file_type.license'))
                            ->schema([
                                FileUpload::make('certificate_path')
                                    ->label(__('admin.ktv_apply.file_type.license'))
                                    ->directory(fn($record) => DirectFile::makePathById(DirectFile::KTVA, $record?->id ?? Helper::getTimestampAsId()))
                                    ->disk('private')
                                    ->nullable()
                                    ->image()
                                    ->maxSize(102400)
                                    ->downloadable()
                                    ->columnSpanFull()
                                    ->deletable()
                                    ->afterStateHydrated(fn($component, $record) => $component->state($record?->certificate()->first()?->file_path)),
                            ])->columnSpanFull(),

                        Section::make(__('admin.ktv_apply.file_type.face_with_identity_card'))
                            ->schema([
                                FileUpload::make('face_with_identity_card_path')
                                    ->label(__('admin.ktv_apply.file_type.face_with_identity_card'))
                                    ->directory(fn($record) => DirectFile::makePathById(DirectFile::KTVA, $record?->id ?? Helper::getTimestampAsId()))
                                    ->disk('private')
                                    ->required()
                                    ->image()
                                    ->maxSize(102400)
                                    ->downloadable()
                                    ->columnSpanFull()
                                    ->deletable()
                                    ->afterStateHydrated(fn($component, $record) => $component->state($record?->faceWithIdentityCard()->first()?->file_path)),
                            ])->columnSpanFull(),

                        Repeater::make('gallery')
                            ->label(__('admin.ktv_apply.file_type.ktv_image_display'))
                            ->relationship('gallery')
                            ->grid(2)
                            ->schema([
                                Hidden::make('type')
                                    ->default(UserFileType::KTV_IMAGE_DISPLAY),
                                FileUpload::make('file_path')
                                    ->label(__('admin.ktv_apply.file_type.ktv_image_display'))
                                    ->directory(fn($record) => DirectFile::makePathById(DirectFile::KTVA, $record?->id ?? Helper::getTimestampAsId()))
                                    ->disk('public')
                                    ->required()
                                    ->image()
                                    ->maxSize(102400)
                                    ->downloadable()
                                    ->columnSpanFull(),
                                Hidden::make('role')
                                    ->default(fn($record) => $record?->role ?? UserRole::KTV->value),
                                Hidden::make('is_public')
                                    ->default(true),
                            ])
                            ->minItems(3)
                            ->maxItems(5)
                            ->defaultItems(3)
                            ->validationMessages([
                                'min' => __('common.error.min_items', ['min' => 3]),
                                'max' => __('common.error.max_items', ['max' => 5]),
                            ])
                            ->helperText(__('common.notice.image_gallery'))
                            ->columnSpanFull(),
                    ])->columns(2),

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
