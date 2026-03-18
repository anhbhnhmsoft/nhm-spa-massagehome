<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use App\Enums\Admin\AdminRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Thông tin cơ bản
                Section::make(__('admin.common.table.basic_info'))
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('id')
                                    ->label(__('admin.common.table.id'))
                                    ->hiddenOn("create")
                                    ->disabled(),
                                TextInput::make('username')
                                    ->label(__('admin.common.table.username'))
                                    ->maxLength(20)
                                    ->required(fn($livewire) => $livewire instanceof CreateRecord)
                                    ->disabled(fn ($livewire) => $livewire instanceof EditRecord)
                                    ->unique(ignoreRecord: true)
                                    ->validationMessages([
                                        'max' => __('common.error.max_length', ['max' => 20]),
                                        'required' => __('common.error.required'),
                                        'unique' => __('common.error.unique'),
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
                                TextInput::make('name')
                                    ->label(__('admin.common.table.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                        'max' => __('common.error.max_length', ['max' => 255])
                                    ]),
                                Select::make('role')
                                    ->label(__('admin.common.table.role'))
                                    ->options(AdminRole::toOptions())
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),

                                Toggle::make('is_active')
                                    ->label(__('admin.common.table.status'))
                                    ->columnSpanFull()
                                    ->default(true),
                            ]),

                    ])
                    ->compact()
                    ->columnSpanFull(),
            ]);
    }
}
