<?php

namespace App\Filament\Resources\StaticContracts\Schemas;

use App\Enums\ContractFileType;
use App\Enums\Language;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class StaticContractForm
{
    public static function configure(Schema $schema): Schema
    {
        $lang = App::getLocale();
        return $schema
            ->components([
                Select::make('type')
                    ->label(__('admin.static_contract.fields.type'))
                    ->options(ContractFileType::toOptions())
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        $set('slug', Str::slug(ContractFileType::getSlug($state)));
                    })
                    ->live()
                    ->required()
                    ->unique('static_contract', 'type')
                    ->validationMessages([
                        'unique' => __('admin.static_contract.errors.type_unique'),
                        'required' => __('admin.static_contract.errors.type_required'),
                    ]),
                TextInput::make('note.' . Language::VIETNAMESE->value)
                    ->label(__('admin.static_contract.fields.note.' . Language::VIETNAMESE->value))
                    ->maxLength(255)
                    ->validationMessages([
                        'max' => __('admin.static_contract.errors.note_max'),
                    ]),
                TextInput::make('note.' . Language::ENGLISH->value)
                    ->label(__('admin.static_contract.fields.note.'. Language::ENGLISH->value))
                    ->maxLength(255)
                    ->validationMessages([
                        'max' => __('admin.static_contract.errors.note_max'),
                    ]),
                TextInput::make('note.' . Language::CHINESE->value)
                    ->label(__('admin.static_contract.fields.note.' . Language::CHINESE->value))
                    ->maxLength(255)
                    ->validationMessages([
                        'max' => __('admin.static_contract.errors.note_max'),
                    ]),
                FileUpload::make('path.' . Language::VIETNAMESE->value)
                    ->label(__('admin.static_contract.fields.path.' . Language::VIETNAMESE->value))
                    ->required()
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10240)
                    ->disk('public')
                    ->validationMessages([
                        'required' => __('admin.static_contract.errors.path_required'),
                        'max' => __('admin.static_contract.errors.path_max'),
                        'mimetypes' => __('admin.static_contract.errors.path_pdf_only'),
                    ])
                    ->downloadable()
                    ->previewable(),
                FileUpload::make('path.' . Language::ENGLISH->value)
                    ->label(__('admin.static_contract.fields.path.' . Language::ENGLISH->value))
                    ->required()
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10240)
                    ->disk('public')
                    ->validationMessages([
                        'required' => __('admin.static_contract.errors.path_required'),
                        'max' => __('admin.static_contract.errors.path_max'),
                        'mimetypes' => __('admin.static_contract.errors.path_pdf_only'),
                    ])
                    ->downloadable()
                    ->previewable(),
                FileUpload::make('path.' . Language::CHINESE->value)
                    ->label(__('admin.static_contract.fields.path.' . Language::CHINESE->value))
                    ->required()
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10240)
                    ->disk('public')
                    ->validationMessages([
                        'required' => __('admin.static_contract.errors.path_required'),
                        'max' => __('admin.static_contract.errors.path_max'),
                        'mimetypes' => __('admin.static_contract.errors.path_pdf_only'),
                    ])
                    ->downloadable()
                    ->previewable()
            ]);
    }
}
