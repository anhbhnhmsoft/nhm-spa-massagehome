<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Schemas;

use App\Core\Helper;
use App\Enums\DirectFile;
use App\Enums\Gender;
use App\Enums\KTVConfigSchedules;
use App\Enums\Language;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserFileType;
use App\Enums\UserRole;
use App\Models\Province;
use App\Services\LocationService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Text;
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
                Section::make(__('admin.ktv_apply.fields.registration_info'))
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
                            ->default(UserRole::KTV->value)
                            ->dehydrateStateUsing(fn() => UserRole::KTV->value)
                            ->dehydrated(true),
                        Hidden::make('status')
                            ->label(__('admin.common.table.status'))
                            ->default(ReviewApplicationStatus::APPROVED),

                        Toggle::make('is_leader')
                            ->label(__('admin.ktv_apply.fields.is_leader'))
                            ->onIcon('heroicon-m-user-group')
                            ->offIcon('heroicon-m-user')
                            ->onColor('success')
                            ->columnSpanFull(),

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

                        TextInput::make('experience')
                            ->label(__('admin.ktv_apply.fields.experience'))
                            ->numeric()
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
                            ->suffix(__('admin.ktv_apply.fields.years')),

                        Textarea::make('bio.' . Language::VIETNAMESE->value)
                            ->label(__('admin.ktv_apply.fields.experience_desc_vi'))
                            ->rows(3)
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->columnSpanFull(),
                        Textarea::make('bio.' . Language::ENGLISH->value)
                            ->label(__('admin.ktv_apply.fields.experience_desc_en'))
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('bio.' . Language::CHINESE->value)
                            ->label(__('admin.ktv_apply.fields.experience_desc_cn'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Thông tin địa điểm
                Section::make(__('admin.ktv_apply.fields.location_info'))
                    ->description(__('admin.ktv_apply.fields.location_info_desc'))
                    ->relationship('reviewApplication')
                    ->aside()
                    ->columnSpanFull()
                    ->compact()
                    ->schema([
                        Select::make('province_code')
                            ->label(__('admin.ktv_apply.fields.province'))
                            ->searchable()
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
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
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->rules(['nullable', 'numeric', 'between:-90,90'])
                            ->columnSpan(1),

                        TextInput::make('longitude')
                            ->label(__('admin.ktv_apply.fields.longitude') ?? 'Longitude')
                            ->numeric()
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->rules(['nullable', 'numeric', 'between:-180,180'])
                            ->columnSpan(1),

                        Textarea::make('address')
                            ->label(__('admin.ktv_apply.fields.address'))
                            ->columnSpanFull()
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->rows(2),
                    ]),

                // Lịch làm việc Kỹ thuật viên
                Section::make(__('admin.ktv_apply.fields.schedule'))
                    ->hidden(function ($record) {
                        $status = $record->reviewApplication?->status;
                        return in_array($status, [
                            ReviewApplicationStatus::PENDING,
                            ReviewApplicationStatus::REJECTED,
                        ]);
                    })
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

                // Thông tin tệp tin
                Section::make(__('admin.ktv_apply.fields.files'))
                    ->columns(4)
                    ->schema([
                        // Hình ảnh CCCD mặt trước
                        FileUpload::make('cccd_front_path')
                            ->label(__('admin.ktv_apply.file_type.identity_card_front'))
                            ->directory(fn($record) => DirectFile::makePathById(DirectFile::KTVA, $record?->id ?? Helper::getTimestampAsId()))
                            ->disk('private')
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->image()
                            ->maxSize(102400)
                            ->downloadable()
                            ->deletable()
                            ->afterStateHydrated(fn($component, $record) => $component->state($record?->cccdFront()->first()?->file_path)),

                        // Hình ảnh CCCD mặt sau
                        FileUpload::make('cccd_back_path')
                            ->label(__('admin.ktv_apply.file_type.identity_card_back'))
                            ->directory(fn($record) => DirectFile::makePathById(DirectFile::KTVA, $record?->id ?? Helper::getTimestampAsId()))
                            ->disk('private')
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->image()
                            ->maxSize(102400)
                            ->downloadable()
                            ->afterStateHydrated(fn($component, $record) => $component->state($record?->cccdBack()->first()?->file_path)),

                        // Hình ảnh khuôn mặt với CCCD
                        FileUpload::make('face_with_identity_card_path')
                            ->label(__('admin.ktv_apply.file_type.face_with_identity_card'))
                            ->directory(fn($record) => DirectFile::makePathById(DirectFile::KTVA, $record?->id ?? Helper::getTimestampAsId()))
                            ->disk('private')
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->image()
                            ->maxSize(102400)
                            ->downloadable()
                            ->deletable()
                            ->afterStateHydrated(fn($component, $record) => $component->state($record?->faceWithIdentityCard()->first()?->file_path)),

                        // Hình ảnh giấy phép KTV
                        FileUpload::make('certificate_path')
                            ->label(__('admin.ktv_apply.file_type.license'))
                            ->directory(fn($record) => DirectFile::makePathById(DirectFile::KTVA, $record?->id ?? Helper::getTimestampAsId()))
                            ->disk('private')
                            ->nullable()
                            ->required()
                            ->validationMessages([
                                'required' => __('common.error.required'),
                            ])
                            ->image()
                            ->maxSize(102400)
                            ->downloadable()
                            ->deletable()
                            ->afterStateHydrated(fn($component, $record) => $component->state($record?->certificate()->first()?->file_path)),

                        Repeater::make('gallery')
                            ->label(__('admin.ktv_apply.file_type.ktv_image_display', ['min' => 3, 'max' => 5]))
                            ->relationship('gallery')
                            ->grid(5)
                            ->schema([
                                Hidden::make('type')
                                    ->default(UserFileType::KTV_IMAGE_DISPLAY),
                                FileUpload::make('file_path')
                                    ->hiddenLabel()
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
