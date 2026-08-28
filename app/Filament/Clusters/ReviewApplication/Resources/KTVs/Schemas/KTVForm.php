<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Schemas;

use App\Core\Helper;
use App\Enums\Admin\AdminGate;
use App\Enums\DirectFile;
use App\Enums\Gender;
use App\Enums\KTVConfigSchedules;
use App\Enums\KtvServiceLocation;
use App\Enums\KtvTechnique;
use App\Enums\Language;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserFileType;
use App\Enums\UserRole;
use App\Filament\Components\CommonFields;
use App\Models\Category;
use App\Models\Province;
use App\Models\User;
use App\Models\UserReviewApplication;
use App\Services\ProvinceService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class KTVForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->disabled(function ($record) {
                if (Gate::allows(AdminGate::ALLOW_PROFILE)){
                    if (!$record) {
                        return false;
                    }
                    $status = $record->reviewApplication?->status;
                    return in_array($status, [
                        ReviewApplicationStatus::PENDING,
                        ReviewApplicationStatus::REJECTED,
                    ]);
                }
                return true;
            })
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])
                    ->columnSpanFull()
                    ->schema([
                        // --- CỘT TRÁI: Thông tin cơ bản, Kỹ thuật & Dịch vụ thế mạnh, Khu vực hoạt động ---
                        Group::make([
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
                                                ->disabled(),
                                            TextInput::make('email')
                                                ->label(__('admin.common.table.email'))
                                                ->email()
                                                ->maxLength(255)
                                                ->disabled(),
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
                                            Select::make('gender')
                                                ->label(__('admin.common.table.gender'))
                                                ->options(Gender::toOptions())
                                                ->required()
                                                ->placeholder(__('common.placeholder.select'))
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

                            // Kỹ thuật chuyên môn & Dịch vụ thế mạnh
                            Section::make(__('admin.ktv_apply.fields.expertise_section'))
                                ->relationship('reviewApplication')
                                ->compact()
                                ->schema([
                                    CheckboxList::make('techniques')
                                        ->label(__('admin.ktv_apply.fields.techniques'))
                                        ->options(KtvTechnique::toOptions())
                                        ->columns(3),
                                    Select::make('strength_service_ids')
                                        ->label(__('admin.ktv_apply.fields.strength_service_ids'))
                                        ->multiple()
                                        ->maxItems(3)
                                        ->options(fn () => Category::toOptions())
                                        ->placeholder(__('common.placeholder.select'))
                                        ->columnSpanFull(),
                                ]),

                            // Địa bàn & Địa điểm làm việc
                            Section::make(__('admin.ktv_apply.fields.area_section'))
                                ->relationship('reviewApplication')
                                ->compact()
                                ->schema([
                                    CheckboxList::make('service_locations')
                                        ->label(__('admin.ktv_apply.fields.service_locations'))
                                        ->options(KtvServiceLocation::toOptions())
                                        ->columns(2),
                                    Select::make('work_province')
                                        ->label(__('admin.ktv_apply.fields.work_province'))
                                        ->searchable()
                                        ->options(ProvinceService::toOptions())
                                        ->placeholder(__('common.placeholder.select'))
                                        ->live()
                                        ->afterStateUpdated(fn ($set) => $set('work_wards', null)),
                                    Select::make('work_wards')
                                        ->label(__('admin.ktv_apply.fields.work_wards'))
                                        ->searchable()
                                        ->disabled(fn ($get) => blank($get('work_province')))
                                        ->placeholder(fn ($get) => blank($get('work_province')) ? __('admin.ktv_apply.fields.select_province_first') : __('common.placeholder.select'))
                                        ->formatStateUsing(function ($state) {
                                            if (is_array($state)) {
                                                return $state[0] ?? null;
                                            }
                                            return $state;
                                        })
                                        ->dehydrateStateUsing(function ($state) {
                                            if (empty($state)) {
                                                return null;
                                            }
                                            return is_array($state) ? $state : [$state];
                                        })
                                        ->options(function ($get, $record) {
                                            $province = $get('work_province');
                                            if (blank($province)) {
                                                return [];
                                            }
                                            $wards = ProvinceService::getWardsByProvince($province);
                                            $currentWard = $get('work_wards') ?? ($record?->reviewApplication?->work_wards ?? $record?->work_wards ?? null);
                                            if (is_array($currentWard)) {
                                                $currentWard = $currentWard[0] ?? null;
                                            }
                                            if (!empty($currentWard) && !isset($wards[$currentWard])) {
                                                $wards[$currentWard] = $currentWard;
                                            }
                                            return $wards;
                                        })
                                        ->createOptionForm([
                                            TextInput::make('ward')->label(__('admin.customer.fields.ward'))->required(),
                                        ])
                                        ->createOptionUsing(fn ($data) => $data['ward']),
                                    Toggle::make('is_online')
                                        ->label(__('admin.ktv_apply.fields.is_online'))
                                        ->default(true)
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->columnSpan(1),

                        // --- CỘT PHẢI: Thông tin đăng ký, Trạng thái xác thực MasaHome ---
                        Group::make([
                            // Thông tin đăng ký
                            Section::make(__('admin.ktv_apply.fields.registration_info'))
                                ->relationship('reviewApplication')
                                ->compact()
                                ->afterHeader([
                                    Text::make(function ($record) {
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
                                        ->onColor('success'),

                                    Toggle::make('is_priority')
                                        ->label(__('admin.ktv_apply.fields.is_priority'))
                                        ->onIcon('heroicon-m-arrow-up-circle')
                                        ->offIcon('heroicon-m-arrow-down-circle')
                                        ->onColor('warning')
                                        ->default(false),

                                    TextInput::make('nickname')
                                        ->label(__('admin.ktv_apply.fields.nickname'))
                                        ->maxLength(255)
                                        ->required()
                                        ->validationMessages([
                                            'required' => __('common.error.required'),
                                            'max' => __('common.error.max_length', ['max' => 255]),
                                        ]),

                                    CommonFields::SelectReferrerIdForKTVAndAgency(),

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

                                    // Trạng thái xác thực MasaHome
                                    Section::make(__('admin.ktv_apply.fields.verification_section'))
                                        ->schema([
                                            TextInput::make('contact_phone')
                                                ->label(__('admin.ktv_apply.fields.contact_phone'))
                                                ->tel()
                                                ->maxLength(20),
                                            Toggle::make('contact_verified')
                                                ->label(__('admin.ktv_apply.fields.contact_verified'))
                                                ->onColor('success')
                                                ->extraFieldWrapperAttributes(['style' => 'display: flex; align-items: center; height: 100%; padding-top: 1.5rem;']),
                                            Toggle::make('portrait_verified')
                                                ->label(__('admin.ktv_apply.fields.portrait_verified'))
                                                ->onColor('success')
                                                ->live()
                                                ->afterStateUpdated(function ($state, $set) {
                                                    if ($state) {
                                                        $set('portrait_verified_at', now());
                                                    } else {
                                                        $set('portrait_verified_at', null);
                                                    }
                                                })
                                                ->extraFieldWrapperAttributes(['style' => 'display: flex; align-items: center; height: 100%; padding-top: 1.5rem;']),
                                            DateTimePicker::make('portrait_verified_at')
                                                ->label(__('admin.ktv_apply.fields.portrait_verified_at'))
                                                ->disabled(),
                                            Toggle::make('certificate_verified')
                                                ->label(__('admin.ktv_apply.fields.certificate_verified'))
                                                ->onColor('success'),
                                        ])
                                        ->columns(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ])
                        ->columnSpan(1),
                    ]),

                // Lịch làm việc Kỹ thuật viên
                Section::make(__('admin.ktv_apply.fields.schedule'))
                    ->hidden(function ($record) {
                        $status = $record?->reviewApplication?->status;
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
                            ->grid(1)
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Select::make('day_key')
                                            ->label(__('admin.ktv_apply.fields.day_key'))
                                            ->placeholder(__('common.placeholder.select'))
                                            ->options([
                                                KTVConfigSchedules::MONDAY->value => __('admin.ktv_apply.fields.monday'),
                                                KTVConfigSchedules::TUESDAY->value => __('admin.ktv_apply.fields.tuesday'),
                                                KTVConfigSchedules::WEDNESDAY->value => __('admin.ktv_apply.fields.wednesday'),
                                                KTVConfigSchedules::THURSDAY->value => __('admin.ktv_apply.fields.thursday'),
                                                KTVConfigSchedules::FRIDAY->value => __('admin.ktv_apply.fields.friday'),
                                                KTVConfigSchedules::SATURDAY->value => __('admin.ktv_apply.fields.saturday'),
                                                KTVConfigSchedules::SUNDAY->value => __('admin.ktv_apply.fields.sunday'),
                                            ])
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(1),

                                        Toggle::make('active')
                                            ->label(__('admin.ktv_apply.fields.is_working'))
                                            ->inline(false)
                                            ->live() // Đổi từ reactive() sang live() nếu bạn dùng Filament v3
                                            ->columnSpan(1),

                                        TimePicker::make('start_time')
                                            ->label(__('admin.ktv_apply.fields.start_time'))
                                            ->format('H:i')
                                            ->displayFormat('H:i')
                                            ->seconds(false)
                                            ->hidden(fn ($get) => !$get('active'))
                                            ->required(fn ($get) => $get('active'))
                                            ->live() // Cần live() để re-render hint ở end_time
                                            ->columnSpan(1),

                                        TimePicker::make('end_time')
                                            ->label(__('admin.ktv_apply.fields.end_time'))
                                            ->format('H:i')
                                            ->displayFormat('H:i')
                                            ->seconds(false)
                                            ->hidden(fn ($get) => !$get('active'))
                                            ->required(fn ($get) => $get('active'))
                                            ->live() // Cần live() để kiểm tra logic xuyên đêm ngay khi Admin chọn
                                            ->hint(function ($get) {
                                                $start = $get('start_time');
                                                $end = $get('end_time');

                                                // Nếu giờ kết thúc nhỏ hơn giờ bắt đầu -> Ca xuyên đêm
                                                if ($start && $end && $end < $start) {
                                                    return __('admin.ktv_apply.fields.is_cross_day');
                                                }

                                                return null;
                                            })
                                            ->hintColor('warning') // Hiển thị màu cam cảnh báo
                                            ->hintIcon('heroicon-m-moon') // Thêm icon mặt trăng cho ngầu (hoặc heroicon-m-clock)
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
                            ->imageEditor()
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
                            ->imageEditor()
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
                            ->imageEditor()
                            ->maxSize(102400)
                            ->downloadable()
                            ->deletable()
                            ->afterStateHydrated(fn($component, $record) => $component->state($record?->faceWithIdentityCard()->first()?->file_path)),

                        // Hình ảnh giấy phép / chứng chỉ KTV
                        FileUpload::make('certificate_path')
                            ->label(__('admin.ktv_apply.fields.certificates_upload'))
                            ->directory(fn($record) => DirectFile::makePathById(DirectFile::KTVA, $record?->id ?? Helper::getTimestampAsId()))
                            ->disk('private')
                            ->nullable()
                            ->image()
                            ->multiple()
                            ->maxSize(102400)
                            ->downloadable()
                            ->deletable()
                            ->openable()
                            ->afterStateHydrated(fn($component, $record) => $component->state($record?->certificate()?->pluck('file_path')->toArray() ?? [])),

                        // Hình ảnh hiển thị KTV
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
                                    ->imageEditor()
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
