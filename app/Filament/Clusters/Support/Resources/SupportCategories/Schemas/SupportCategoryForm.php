<?php

namespace App\Filament\Clusters\Support\Resources\SupportCategories\Schemas;

use App\Enums\Admin\AdminGate;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class SupportCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->disabled(fn () => ! Gate::allows(AdminGate::ALLOW_ADMIN))
            ->components([
                Section::make(__('admin.common.support_category.section.general'))
                    ->compact()
                    ->columns(12)
                    ->schema([
                        Toggle::make('is_active')
                            ->label(__('admin.common.form.is_active'))
                            ->default(true)
                            ->columnSpan(6),
                        TextInput::make('position')
                            ->label(__('admin.common.form.position'))
                            ->helperText(__('admin.common.support_category.helper.position'))
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->columnSpan(6),
                    ]),
                Section::make(__('admin.common.support_category.section.content'))
                    ->compact()
                    ->columns(12)
                    ->schema([
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
                                    ->rows(4)
                                    ->columnSpan(1),
                                Textarea::make('description.en')
                                    ->label(__('admin.common.form.description_en'))
                                    ->rows(4)
                                    ->columnSpan(1),
                                Textarea::make('description.cn')
                                    ->label(__('admin.common.form.description_cn'))
                                    ->rows(4)
                                    ->columnSpan(1),
                            ]),
                        Grid::make()
                            ->columnSpanFull()
                            ->columns(3)
                            ->schema([
                                Textarea::make('message.vi')
                                    ->label(__('admin.common.form.message_vi'))
                                    ->rows(3)
                                    ->columnSpan(1),
                                Textarea::make('message.en')
                                    ->label(__('admin.common.form.message_en'))
                                    ->rows(3)
                                    ->columnSpan(1),
                                Textarea::make('message.cn')
                                    ->label(__('admin.common.form.message_cn'))
                                    ->rows(3)
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }
}
