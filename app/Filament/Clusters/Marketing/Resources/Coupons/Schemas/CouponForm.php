<?php

namespace App\Filament\Clusters\Marketing\Resources\Coupons\Schemas;

use App\Enums\DirectFile;
use App\Enums\Language;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        $lang = App::getLocale();
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('code')
                                    ->label(__('admin.coupon.fields.code'))
                                    ->required()
                                    ->unique()
                                    ->maxLength(255)
                                    ->default(fn() => Str::random(8))
                                    ->validationMessages([
                                        'required' => __("common.error.required"),
                                        'unique' => __("common.error.unique"),
                                        'max' => __("common.error.max"),
                                    ]),
                                TextInput::make('label.' . $lang)
                                    ->label(__('admin.coupon.fields.label'))
                                    ->required()
                                    ->maxLength(255)
                                    ->validationMessages([
                                        'required' => __("common.error.required"),
                                        'max' => __("common.error.max"),
                                    ])
                                    ->filled(),
                                Textarea::make('description.' . $lang)
                                    ->label(__('admin.coupon.fields.description'))
                                    ->required()
                                    ->validationMessages([
                                        'required' => __("common.error.required"),
                                    ]),
                                Select::make('is_percentage')
                                    ->label(__('admin.coupon.fields.is_percentage'))
                                    ->required()
                                    ->options([
                                        true => __('admin.coupon.is_percentage.percent'),
                                        false => __('admin.coupon.is_percentage.fixed'),
                                    ]),
                                DateTimePicker::make('start_at')
                                    ->label(__('admin.coupon.fields.start_date'))
                                    ->required(),
                                DateTimePicker::make('end_at')
                                    ->label(__('admin.coupon.fields.end_date'))
                                    ->required(),
                                TextInput::make('discount_value')
                                    ->label(__('admin.coupon.fields.discount_value'))
                                    ->required()
                                    ->numeric()
                                    ->validationMessages([
                                        'required' => __("common.error.required"),
                                        'numeric' => __("common.error.numeric"),
                                    ]),
                                TextInput::make('max_discount')
                                    ->label(__('admin.coupon.fields.max_discount'))
                                    ->required()
                                    ->numeric()
                                    ->validationMessages([
                                        'required' => __("common.error.required"),
                                        'numeric' => __("common.error.numeric"),
                                    ]),
                                TextInput::make('usage_limit')
                                    ->label(__('admin.coupon.fields.usage_limit'))
                                    ->required()
                                    ->numeric()
                                    ->validationMessages([
                                        'required' => __("common.error.required"),
                                        'numeric' => __("common.error.numeric"),
                                    ]),
                                TextInput::make('used_count')
                                    ->label(__('admin.coupon.fields.used_count'))
                                    ->required()
                                    ->default(0)
                                    ->disabled(),
                                Toggle::make('is_active')
                                    ->label(__('admin.coupon.fields.is_active'))
                                    ->default(true)
                                    ->required(),
                                Toggle::make('display_ads')
                                    ->label(__('admin.coupon.fields.display_ads'))
                                    ->default(true)
                                    ->required(),
                                Grid::make()
                                    ->columns(3)
                                    ->schema([
                                        FileUpload::make('banners.' . Language::VIETNAMESE->value)
                                            ->label(__('admin.coupon.fields.banners.' . Language::VIETNAMESE->value))
                                            ->image()
                                            ->disk('public')
                                            ->directory(DirectFile::COUPON->value)
                                            ->required()
                                            ->validationMessages([
                                                'required' => __("common.error.required"),
                                            ]),
                                        FileUpload::make('banners.' . Language::ENGLISH->value)
                                            ->label(__('admin.coupon.fields.banners.' . Language::ENGLISH->value))
                                            ->image()
                                            ->disk('public')
                                            ->directory(DirectFile::COUPON->value)
                                            ->required()
                                            ->validationMessages([
                                                'required' => __("common.error.required"),
                                            ]),
                                        FileUpload::make('banners.' . Language::CHINESE->value)
                                            ->label(__('admin.coupon.fields.banners.' . Language::CHINESE->value))
                                            ->image()
                                            ->disk('public')
                                            ->directory(DirectFile::COUPON->value)
                                            ->required()
                                            ->validationMessages([
                                                'required' => __("common.error.required"),
                                            ]),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
