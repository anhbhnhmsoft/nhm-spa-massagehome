<?php

namespace App\Filament\Clusters\Service\Resources\Services\Schemas;

use App\Enums\DirectFile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        $lang = app()->getLocale();
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name.' . $lang)
                                    ->label(__('admin.service.fields.name'))
                                    ->required()
                                    ->maxLength(255),

                                Select::make('category_id')
                                    ->label(__('admin.service.fields.category'))
                                    ->relationship(
                                        name: 'category',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn(Builder $query) => $query
                                            ->orderByRaw("name->>'{$lang}' ASC")
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('user_id')
                                    ->label(__('admin.service.fields.provider'))
                                    ->relationship('provider', 'name')
                                    ->required(),
                                FileUpload::make('image_url')
                                    ->label(__('admin.service.fields.image'))
                                    ->required()
                                    ->disk('public')
                                    ->image()
                                    ->directory(DirectFile::SERVICE->value)
                                    ->image(),
                                Toggle::make('is_active')
                                    ->label(__('admin.service.fields.status'))
                                    ->required()
                                    ->default(true),
                                RichEditor::make('description.' . $lang)
                                    ->label(__('admin.service.fields.description'))
                                    ->required(),
                            ]),
                        Section::make()
                            ->schema([
                                Repeater::make('options')
                                    ->relationship('options')
                                    ->schema([
                                        Grid::make()
                                            ->schema([
                                                TextInput::make('duration')
                                                    ->label(__('admin.service.fields.duration'))
                                                    ->required(),
                                                TextInput::make('price')
                                                    ->label(__('admin.service.fields.price'))
                                                    ->numeric()
                                                    ->suffix(__('admin.common.currency'))
                                                    ->required(),
                                            ])
                                    ])
                                    ->label(__('admin.service.fields.options'))
                            ])
                    ])
                    ->columnSpan('full')
            ]);
    }
}
