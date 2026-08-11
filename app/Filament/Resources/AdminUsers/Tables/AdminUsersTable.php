<?php

namespace App\Filament\Resources\AdminUsers\Tables;

use App\Enums\Admin\AdminRole;
use App\Enums\Admin\AdminGate;
use App\Enums\Language;
use App\Filament\Components\CommonFields;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class AdminUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                CommonFields::IdColumn(),
                TextColumn::make('name')
                    ->searchable()
                    ->label(__('admin.common.table.name')),
                TextColumn::make('username')
                    ->searchable()
                    ->label(__('admin.common.table.username')),
                TextColumn::make('language')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof Language
                        ? $state->label()
                        : (Language::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->label(__('admin.common.table.language')),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (AdminRole $state): string => $state->label())
                    ->label(__('admin.common.table.role')),
                TextColumn::make('is_active')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state
                        ? __('admin.common.status.active')
                        : __('admin.common.status.inactive'))
                    ->color(fn ($state): string => $state ? 'success' : 'gray')
                    ->label(__('admin.common.table.status')),
                TextColumn::make('last_seen_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label(__('admin.common.table.is_online')),
                TextColumn::make('created_at')
                    ->dateTime('H:i d/m/Y')
                    ->label(__('admin.common.table.created_at')),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(fn (): array => Gate::allows(AdminGate::ALLOW_SUPER_ADMIN)
                        ? AdminRole::toOptions()
                        : AdminRole::managementOptions())
                    ->label(__('admin.common.table.role')),
                SelectFilter::make('is_active')
                    ->options([
                        1 => __('admin.common.status.active'),
                        0 => __('admin.common.status.inactive'),
                    ])
                    ->label(__('admin.common.table.status')),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make('edit')
                    ->label(__('admin.common.action.edit'))
                    ->icon('heroicon-o-identification'),
                DeleteAction::make()
                    ->label(__('admin.common.action.delete'))
                    ->tooltip(__('admin.common.tooltip.delete'))
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->id === auth()->id())
                    ->modalHeading(__('admin.common.modal.delete_title'))
                    ->modalDescription(__('admin.common.modal.delete_confirm'))
                    ->modalSubmitActionLabel(__('admin.common.action.confirm_delete'))
            ]);

    }
}
