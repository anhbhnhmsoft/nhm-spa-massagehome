<?php

namespace App\Filament\Clusters\Agency\Resources\Agencies\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AgenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.common.table.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label(__('admin.common.table.phone'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reviewApplication.province.name')
                    ->label(__('admin.ktv_apply.fields.province'))
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('reviewApplication.status')
                    ->label(__('admin.common.table.status'))
                    ->badge()
                    ->formatStateUsing(fn($state) => $state?->label())
                    ->color(fn($state) => $state?->color())
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('admin.common.table.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                ToggleColumn::make('is_active')
                    ->label(__('admin.common.table.status'))
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label(__('admin.common.action.view'))
                        ->tooltip(__('admin.common.tooltip.view'))
                        ->icon('heroicon-o-eye'),
                ]),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('is_active')
                    ->options([
                        true => __('admin.common.status.active'),
                        false => __('admin.common.status.inactive'),
                    ])
                    ->label(__('admin.common.filter.status')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('admin.common.action.delete'))
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.common.modal.delete_title'))
                        ->modalDescription(__('admin.common.modal.delete_confirm'))
                        ->modalSubmitActionLabel(__('admin.common.action.confirm_delete')),

                    RestoreBulkAction::make()
                        ->label(__('admin.common.action.restore'))
                        ->visible(fn($livewire) => $livewire->tableFilters['trashed']['value'] ?? null === 'only'),

                    ForceDeleteBulkAction::make()
                        ->label(__('admin.common.action.force_delete'))
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.common.modal.force_delete_title'))
                        ->modalDescription(__('admin.common.modal.force_delete_confirm'))
                        ->modalSubmitActionLabel(__('admin.common.action.confirm_delete')),
                ]),
            ]);
    }
}
