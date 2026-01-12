<?php

namespace App\Filament\Clusters\Category\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\App;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        $lang = App::getLocale();
        return $schema
            ->components([
                // === SECTION 1: THÔNG TIN CHUNG ===
                Section::make(__('admin.category.basic_info')) // Đặt tiêu đề nếu muốn
                ->columns(2)
                    ->schema([
                        TextInput::make('name.' . $lang)
                            ->label(__('admin.common.form.name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('position')
                            ->label(__('admin.common.form.position'))
                            ->required()
                            ->numeric() // Dùng numeric thay vì integer để validation chuẩn hơn với input
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(1),

                        Textarea::make('description.' . $lang)
                            ->label(__('admin.common.form.description'))
                            ->rows(3)
                            ->columnSpanFull(), // Cho mô tả rộng full dòng

                        FileUpload::make('image_url')
                            ->disk('public')
                            ->label(__('admin.common.form.image'))
                            ->image()
                            ->columnSpanFull(),

                        Toggle::make('is_featured')
                            ->label(__('admin.common.form.is_featured'))
                            ->default(false),

                        Toggle::make('is_active')
                            ->label(__('admin.common.form.is_active'))
                            ->default(true)
                            ->disabled(fn($livewire) => $livewire instanceof CreateRecord),
                    ]),

                // === SECTION 2: CẤU HÌNH GIÁ (REPEATER) ===
                Section::make(__('admin.category.price_config'))
                    ->schema([
                        Repeater::make('prices')
                            ->label(__('admin.category.price_list'))
                            ->relationship() // Quan trọng: Báo cho Filament biết đây là quan hệ HasMany
                            ->schema([
                                TextInput::make('duration')
                                    ->label(__('admin.common.form.duration'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->suffix(__('admin.common.minute')),

                                TextInput::make('price')
                                    ->label(__('admin.common.form.price'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix(__('admin.currency'))
                                    ->formatStateUsing(fn($state) => number_format($state, 0, '.', '')),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel(__('admin.common.form.add_price'))
                            ->reorderableWithButtons()
                    ]),
            ]);
    }
}
