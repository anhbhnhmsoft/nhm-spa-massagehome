<?php

namespace App\Filament\Clusters\Marketing\Resources\PageStatics\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\App;

class PageStaticForm
{
    public static function configure(Schema $schema): Schema
    {   
        $lang = App::getLocale();
        return $schema
            ->components([
                Section::make()
                ->components([
                    Grid::make()
                    ->columnSpan('full')
                    ->schema([
                        TextInput::make("title.$lang")
                        ->label(__('admin.page_static.title'))
                        ->required()
                        ->maxLength(255)
                        ->validationMessages([
                                'required' => __('common.error.required'),
                                'max'      => __('common.error.max_length', ['max' => 255])
                            ]),
                    TextInput::make("meta_title.$lang")
                    ->label(__('admin.page_static.meta_title'))
                    ->required()    
                    ->maxLength(255)
                    ->validationMessages([
                                'required' => __('common.error.required'),
                                'max'      => __('common.error.max_length', ['max' => 255])
                            ]),
                    TextInput::make("meta_description.$lang")
                    ->label(__('admin.page_static.meta_description'))
                    ->required()
                    ->maxLength(255)
                    ->validationMessages([
                                'required' => __('common.error.required'),
                                'max'      => __('common.error.max_length', ['max' => 255])
                            ]),
                    TextInput::make("meta_keywords.$lang")
                    ->label(__('admin.page_static.meta_keywords'))
                    ->required()
                    ->maxLength(255)
                    ->validationMessages([
                                'required' => __('common.error.required'),
                                'max'      => __('common.error.max_length', ['max' => 255])
                            ]),
                    TextInput::make('slug')
                    ->label(__('admin.page_static.slug'))
                    ->required()
                    ->unique()
                    ->maxLength(255)
                    ->validationMessages([
                                'required' => __('common.error.required'),
                                'max'      => __('common.error.max_length', ['max' => 255]),
                                'unique'   => __('common.error.unique'), 
                            ]),
                    Toggle::make('is_active')
                    ->label(__('admin.page_static.is_active'))
                    ->required()
                    ->validationMessages([
                                'required' => __('common.error.required'),
                            ]),
                            ]),
                            Grid::make(12)
            ->schema([
                    RichEditor::make("content.$lang")
                    ->label(__('admin.page_static.content'))
                    ->required()
                    ->columnSpan(9)
                    ->columnStart(2)
                    ->validationMessages([
                                'required' => __('common.error.required'),
                    ])
                            ]),
                    ])
                ->columnSpan('full'),
            ]);
    }
}
