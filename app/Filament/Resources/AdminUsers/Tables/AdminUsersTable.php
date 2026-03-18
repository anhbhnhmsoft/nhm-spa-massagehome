<?php

namespace App\Filament\Resources\AdminUsers\Tables;

use App\Enums\Admin\AdminRole;
use App\Filament\Components\CommonFields;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (AdminRole $state): string => $state->label())
                    ->label(__('admin.common.table.role')),
                TextColumn::make('created_at')
                    ->dateTime('H:i d/m/Y')
                    ->label(__('admin.common.table.created_at')),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(AdminRole::toOptions())
                    ->label(__('admin.common.table.role')),
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
