<?php

namespace App\Filament\Clusters\User\Resources\Customers\Schemas;

use App\Enums\Admin\AdminGate;
use App\Enums\CustomerRank;
use App\Enums\DemandStatus;
use App\Enums\DirectFile;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\PreferredTimeSlot;
use App\Enums\KtvTechnique;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Province;
use App\Models\User;
use App\Services\ProvinceService;
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
            ->disabled(fn(): bool => !Gate::allows(AdminGate::ALLOW_PROFILE))
            ->components([
                // 1. Thông tin cơ bản & Hồ sơ
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
                                    ->nullable()
                                    ->downloadable()
                                    ->alignCenter()
                                    ->maxSize(102400),
                                Textarea::make('bio')
                                    ->label(__('admin.common.table.bio'))
                                    ->rows(3),
                                TextInput::make('temp_address')
                                    ->label(__('admin.common.table.address'))
                                    ->columnSpanFull(),
                                Select::make('province')
                                    ->label(__('admin.customer.fields.province'))
                                    ->searchable()
                                    ->options(ProvinceService::toOptions())
                                    ->placeholder(__('common.placeholder.select'))
                                    ->live()
                                    ->afterStateUpdated(fn ($set) => $set('ward', null)),
                                Select::make('ward')
                                    ->label(__('admin.customer.fields.ward'))
                                    ->searchable()
                                    ->disabled(fn ($get) => blank($get('province')))
                                    ->placeholder(fn ($get) => blank($get('province')) ? __('admin.customer.fields.select_province_first') : __('common.placeholder.select'))
                                    ->options(function ($get, $record) {
                                        $province = $get('province');
                                        if (blank($province)) {
                                            return [];
                                        }
                                        $wards = ProvinceService::getWardsByProvince($province);
                                        $currentWard = $get('ward') ?? ($record?->profile?->ward ?? $record?->ward ?? null);
                                        if (!empty($currentWard) && !isset($wards[$currentWard])) {
                                            $wards = [$currentWard => $currentWard] + $wards;
                                        }
                                        return $wards;
                                    })
                                    ->createOptionForm([
                                        TextInput::make('ward')->label(__('admin.customer.fields.ward'))->required(),
                                    ])
                                    ->createOptionUsing(fn ($data) => $data['ward']),
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
                    ->columns(2)
                    ->columnSpanFull(),

                // 2. Nhu cầu dịch vụ & Vị trí
                Section::make(__('admin.customer.section.service_needs'))
                    ->relationship('crmData')
                    ->schema([
                        Select::make('languages')
                            ->label(__('admin.customer.fields.languages'))
                            ->multiple()
                            ->options(Language::toOptions())
                            ->placeholder(__('common.placeholder.select')),
                        Select::make('preferred_services')
                            ->label(__('admin.customer.fields.preferred_services'))
                            ->multiple()
                            ->options(fn () => Category::toOptions())
                            ->placeholder(__('common.placeholder.select')),
                        Select::make('preferred_techniques')
                            ->label(__('admin.customer.fields.preferred_techniques'))
                            ->multiple()
                            ->options(KtvTechnique::toOptions())
                            ->placeholder(__('common.placeholder.select')),
                        Select::make('preferred_time_slots')
                            ->label(__('admin.customer.fields.preferred_time_slots'))
                            ->multiple()
                            ->options(PreferredTimeSlot::toOptions())
                            ->placeholder(__('common.placeholder.select')),
                        TextInput::make('address_detail')
                            ->label(__('admin.customer.fields.address_detail'))
                            ->columnSpanFull(),
                    ])
                    ->compact()
                    ->columns(2)
                    ->columnSpanFull(),

                // 3. Quản lý CRM & Chăm sóc khách hàng
                Section::make(__('admin.customer.section.crm_management'))
                    ->relationship('crmData')
                    ->schema([
                        Select::make('customer_rank')
                            ->label(__('admin.customer.fields.customer_rank'))
                            ->options(CustomerRank::toOptions())
                            ->default(CustomerRank::STANDARD->value)
                            ->placeholder(__('common.placeholder.select')),
                        Select::make('demand_status')
                            ->label(__('admin.customer.fields.demand_status'))
                            ->options(DemandStatus::toOptions())
                            ->default(DemandStatus::EXPLORING->value)
                            ->placeholder(__('common.placeholder.select')),
                        Select::make('assigned_cskh_id')
                            ->label(__('admin.customer.fields.assigned_cskh'))
                            ->options(fn() => AdminUser::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder(__('common.placeholder.select')),
                        TextInput::make('total_spent')
                            ->label(__('admin.customer.fields.total_spent'))
                            ->numeric()
                            ->prefix('₫')
                            ->disabled(),
                        TextInput::make('booking_count')
                            ->label(__('admin.customer.fields.booking_count'))
                            ->numeric()
                            ->disabled(),
                        TextInput::make('aov')
                            ->label(__('admin.customer.fields.aov'))
                            ->numeric()
                            ->prefix('₫')
                            ->disabled(),
                        Textarea::make('cskh_notes')
                            ->label(__('admin.customer.fields.cskh_notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->compact()
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
