<?php

namespace App\Filament\Clusters\Category\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.common.form.name'))
                            ->required()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => __('common.validation.required'),
                                'max' => __('common.validation.max', ['max' => 255]),
                            ]),
                        TextInput::make('position')
                            ->label(__('admin.common.form.position'))
                            ->required()
                            ->maxValue(100)
                            ->integer()
                            ->minValue(1)
                            ->validationMessages([
                                'required' => __('common.validation.required'),
                                'max_value' => __('common.validation.max_value', ['max' => 100]),
                                'min_value' => __('common.validation.min_value', ['min' => 1]),
                            ]),
                        Textarea::make('description')
                            ->label(__('admin.common.form.description'))
                            ->required()
                            ->rows(3)
                            ->validationMessages([
                                'required' => __('common.validation.required'),
                            ]),
                        FileUpload::make('image_url')
                            ->label(__('admin.common.form.image'))
                            ->required()
                            ->image()
                            ->validationMessages([
                                'required' => __('common.validation.required'),
                            ]),
                        Toggle::make('is_featured')
                            ->label(__('admin.common.form.is_featured'))
                            ->required()
                            ->default(false)
                            ->validationMessages([
                                'required' => __('common.validation.required'),
                            ]),
                        Toggle::make('is_active')
                            ->label(__('admin.common.form.is_active'))
                            ->required()
                            ->default(true)
                            ->disabled(fn($livewire) => $livewire instanceof CreateRecord)
                            ->validationMessages([
                                'required' => __('common.validation.required'),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
