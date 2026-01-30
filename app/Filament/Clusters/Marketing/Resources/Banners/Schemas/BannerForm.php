<?php

namespace App\Filament\Clusters\Marketing\Resources\Banners\Schemas;

use App\Enums\BannerType;
use App\Enums\DirectFile;
use App\Enums\Language;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make()
                    ->compact()
                    ->schema([
                        Toggle::make('is_active')
                            ->label(__('admin.banner.fields.is_active'))
                            ->default(true)
                            ->required(),
                        TextInput::make('order')
                            ->label(__('admin.banner.fields.order'))
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->validationMessages([
                                'required' => __("common.error.required"),
                                'numeric' => __("common.error.numeric"),
                            ]),
                        Select::make('type')
                            ->label(__('admin.banner.fields.type'))
                            ->options(BannerType::toOptions())
                            ->required()
                            ->validationMessages([
                                'required' => __("common.error.required"),
                            ]),
                    ]),
                Section::make()
                    ->compact()
                    ->columns(3)
                    ->schema([
                        FileUpload::make('image_url.vi')
                            ->label(__('admin.banner.fields.image_url.vi'))
                            ->image()
                            ->disk('public')
                            ->directory(DirectFile::BANNER->value)
                            ->required()
                            ->maxSize(10240) // 10MB
                            ->imageEditor()
                            ->visibility('public')
                            ->validationMessages([
                                'required' => __("common.error.required"),
                            ]),
                        FileUpload::make('image_url.en')
                            ->label(__('admin.banner.fields.image_url.en'))
                            ->image()
                            ->disk('public')
                            ->directory(DirectFile::BANNER->value)
                            ->required()
                            ->maxSize(10240) // 10MB
                            ->imageEditor()
                            ->visibility('public')
                            ->validationMessages([
                                'required' => __("common.error.required"),
                            ]),
                        FileUpload::make('image_url.cn')
                            ->label(__('admin.banner.fields.image_url.cn'))
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(10240) // 10MB
                            ->imageEditor()
                            ->directory(DirectFile::BANNER->value)
                            ->required()
                            ->validationMessages([
                                'required' => __("common.error.required"),
                            ]),
                    ])
            ]);
    }
}
