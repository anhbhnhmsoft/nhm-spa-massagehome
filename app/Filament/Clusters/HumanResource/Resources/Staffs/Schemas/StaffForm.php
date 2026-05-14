<?php

namespace App\Filament\Clusters\HumanResource\Resources\Staffs\Schemas;

use App\Enums\Admin\AdminRole;
use App\Enums\Language;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.staff.section.account'))
                    ->compact()
                    ->columns(2)
                    ->schema([
                        TextInput::make('username')
                            ->label(__('admin.common.table.username'))
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn ($livewire) => $livewire instanceof EditRecord)
                            ->validationMessages([
                                'required' => __('common.error.required'),
                                'max' => __('common.error.max_length', ['max' => 20]),
                                'unique' => __('common.error.unique'),
                            ]),
                        TextInput::make('password')
                            ->label(__('admin.common.table.password'))
                            ->password()
                            ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->revealable()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => __('common.error.required'),
                                'max' => __('common.error.max_length', ['max' => 255]),
                            ]),
                        TextInput::make('name')
                            ->label(__('admin.common.table.name'))
                            ->required()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => __('common.error.required'),
                                'max' => __('common.error.max_length', ['max' => 255]),
                            ]),
                        Select::make('language')
                            ->label(__('admin.common.table.language'))
                            ->options(Language::toOptions())
                            ->default(Language::VIETNAMESE->value)
                            ->required()
                            ->searchable(),
                        Toggle::make('is_active')
                            ->label(__('admin.common.table.status'))
                            ->default(true),
                        TextInput::make('role')
                            ->default(AdminRole::EMPLOYEE->value)
                            ->hidden()
                            ->dehydrated(true),
                    ]),
            ]);
    }
}
