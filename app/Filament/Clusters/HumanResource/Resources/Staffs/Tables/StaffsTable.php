<?php

namespace App\Filament\Clusters\HumanResource\Resources\Staffs\Tables;

use App\Enums\Language;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StaffsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin.common.table.id'))
                    ->searchable(),
                TextColumn::make('username')
                    ->label(__('admin.common.table.username'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label(__('admin.common.table.name'))
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('language')
                    ->label(__('admin.common.table.language'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof Language ? $state->label() : (Language::tryFrom((string) $state)?->label() ?? (string) $state)),
                TextColumn::make('is_active')
                    ->label(__('admin.common.table.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.common.status.active') : __('admin.common.status.inactive'))
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                TextColumn::make('last_seen_at')
                    ->label(__('admin.common.table.is_online'))
                    ->dateTime()
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label(__('admin.common.table.created_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin.common.table.updated_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('admin.common.table.status'))
                    ->options([
                        1 => __('admin.common.status.active'),
                        0 => __('admin.common.status.inactive'),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('common.action.edit')),
                DeleteAction::make()
                    ->label(__('common.action.delete')),
            ])
            ->bulkActions([])
            ->emptyStateHeading(__('admin.staff.empty_state.heading'))
            ->emptyStateDescription(__('admin.staff.empty_state.description'));
    }
}
