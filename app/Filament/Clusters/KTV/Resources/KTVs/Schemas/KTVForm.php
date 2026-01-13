<?php

namespace App\Filament\Clusters\KTV\Resources\KTVs\Schemas;

use App\Core\Helper;
use App\Enums\DirectFile;
use App\Enums\Gender;
use App\Enums\KTVConfigSchedules;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserFileType;
use App\Enums\UserRole;
use App\Models\Province;
use App\Models\User;
use App\Services\LocationService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
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
                                    ->helperText(__('admin.common.table.password_desc_ktv'))
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                        'max' => __('common.error.max_length', ['max' => 255])
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
                                DatePicker::make('date_of_birth')
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
                        Hidden::make('role')
                            ->default(UserRole::KTV->value)
                            ->dehydrateStateUsing(fn() => UserRole::KTV->value)
                            ->dehydrated(true),
                        Select::make('status')
                            ->label(__('admin.common.table.status'))
                            ->options(ReviewApplicationStatus::toOptions())
                            ->default(ReviewApplicationStatus::APPROVED),
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
                            ->label(__('admin.ktv_apply.fields.latitude') ?? 'Latitude')
                            ->numeric()
                            ->rules(['nullable', 'numeric', 'between:-90,90'])
                            ->columnSpan(1),

                        TextInput::make('longitude')
                            ->label(__('admin.ktv_apply.fields.longitude') ?? 'Longitude')
                            ->numeric()
                            ->rules(['nullable', 'numeric', 'between:-180,180'])
                            ->columnSpan(1),

                        Textarea::make('address')
                            ->label(__('admin.ktv_apply.fields.address'))
                            ->columnSpanFull()
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
                        Hidden::make('role')
                            ->default(UserRole::KTV->value),
                    ])
                    ->columns(2),

                // Thông tin hệ thống
                Section::make(__('admin.ktv_apply.fields.system_info'))
                    ->schema([
                        Select::make('role')
                            ->label(__('admin.common.table.role'))
                            ->options(UserRole::toOptions())
                            ->default(UserRole::KTV->value)
                            ->disabled(),
                        DateTimePicker::make('last_login_at')
                            ->label(__('admin.common.table.last_login'))
                            ->disabled(),
                        Toggle::make('is_active')
                            ->label(__('admin.common.table.status'))
                            ->columnSpanFull()
                            ->default(true),

                    ])
                    ->columnSpanFull(),

                // Lịch làm việc Kỹ thuật viên
                Section::make(__('admin.ktv_apply.fields.schedule'))
                    ->relationship('schedule') // Tên hàm quan hệ trong Model User
                    ->schema([
                        Toggle::make('is_working')
                            ->label(__('admin.ktv_apply.fields.is_working'))
                            ->helperText(__('admin.ktv_apply.fields.is_working_helper'))
                            ->columnSpanFull(),

                        Repeater::make('working_schedule')
                            ->label(__('admin.ktv_apply.fields.working_schedule'))
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->grid(1) // Hiển thị mỗi ngày một dòng cho dễ nhìn
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Select::make('day_key')
                                            ->label(__('admin.ktv_apply.fields.day_key'))
                                            ->options([
                                                KTVConfigSchedules::MONDAY->value => __('admin.ktv_apply.fields.monday'),
                                                KTVConfigSchedules::TUESDAY->value => __('admin.ktv_apply.fields.tuesday'),
                                                KTVConfigSchedules::WEDNESDAY->value => __('admin.ktv_apply.fields.wednesday'),
                                                KTVConfigSchedules::THURSDAY->value => __('admin.ktv_apply.fields.thursday'),
                                                KTVConfigSchedules::FRIDAY->value => __('admin.ktv_apply.fields.friday'),
                                                KTVConfigSchedules::SATURDAY->value => __('admin.ktv_apply.fields.saturday'),
                                                KTVConfigSchedules::SUNDAY->value => __('admin.ktv_apply.fields.sunday'),
                                            ])
                                            ->disabled()       // Người dùng không sửa được
                                            ->dehydrated()     // Vẫn gửi dữ liệu về Backend để lưu vào JSON
                                            ->columnSpan(1),

                                        Toggle::make('active')
                                            ->label(__('admin.ktv_apply.fields.is_working'))
                                            ->inline(false)
                                            ->reactive() // Để ẩn/hiện giờ ngay lập tức
                                            ->columnSpan(1),

                                        TimePicker::make('start_time')
                                            ->label(__('admin.ktv_apply.fields.start_time'))
                                            ->format('H:i')
                                            ->displayFormat('H:i')
                                            ->seconds(false)
                                            ->hidden(fn ( $get) => !$get('active'))
                                            ->required(fn ( $get) => $get('active'))
                                            ->columnSpan(1),

                                        TimePicker::make('end_time')
                                            ->label(__('admin.ktv_apply.fields.end_time'))
                                            ->format('H:i')
                                            ->displayFormat('H:i')
                                            ->seconds(false)
                                            ->hidden(fn ( $get) => !$get('active'))
                                            ->required(fn ( $get) => $get('active'))
                                            ->after('start_time')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->compact(),

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
                                    ->deletable()
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
                                    ->columnSpanFull()
                                    ->deletable(),
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
                    ])->columnSpanFull(),



            ]);
    }
}
