<?php

namespace App\Filament\Clusters\Service\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.category.basic_info'))
                    ->compact()
                    ->columns(2)
                    ->schema([
                        FileUpload::make('image_url')
                            ->disk('public')
                            ->label(__('admin.common.form.image'))
                            ->image()
                            ->columnSpanFull(),
                        Grid::make()
                            ->columnSpanFull()
                            ->columns(3)
                            ->schema([
                                Toggle::make('is_featured')
                                    ->label(__('admin.common.form.is_featured'))
                                    ->default(false),

                                Toggle::make('is_active')
                                    ->label(__('admin.common.form.is_active'))
                                    ->default(true)
                                    ->disabled(fn($livewire) => $livewire instanceof CreateRecord),
                                TextInput::make('position')
                                    ->label(__('admin.common.form.position'))
                                    ->required()
                                    ->numeric() // Dùng numeric thay vì integer để validation chuẩn hơn với input
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->default(1),
                            ]),
                        Grid::make()
                            ->columnSpanFull()
                            ->columns(3)
                            ->schema([
                                TextInput::make('name.vi')
                                    ->label(__('admin.common.form.name_vi'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name.en')
                                    ->label(__('admin.common.form.name_en'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name.cn')
                                    ->label(__('admin.common.form.name_cn'))
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Grid::make()
                            ->columnSpanFull()
                            ->columns(3)
                            ->schema([
                                Textarea::make('description.vi')
                                    ->label(__('admin.common.form.description_vi'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Textarea::make('description.en')
                                    ->label(__('admin.common.form.description_en'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Textarea::make('description.cn')
                                    ->label(__('admin.common.form.description_cn'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),


                    ]),

                Repeater::make('prices')
                    ->label(__('admin.category.price_list'))
                    ->relationship("prices")
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
            ]);
    }
}
