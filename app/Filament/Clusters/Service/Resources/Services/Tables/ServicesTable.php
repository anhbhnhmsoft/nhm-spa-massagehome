<?php

namespace App\Filament\Clusters\Service\Resources\Services\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.service.fields.name'))
                    ->limit(50),
                TextColumn::make('category.name')
                    ->label(__('admin.service.fields.category')),
                TextColumn::make('provider.name')
                    ->searchable()
                    ->label(__('admin.service.fields.provider')),
                ImageColumn::make('image_url')
                    ->label(__('admin.service.fields.image'))
                    ->disk('public'),
                TextColumn::make('options_avg_price')
                    ->label(__('admin.service.fields.options_avg_price'))
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.')),
                ToggleColumn::make('is_active')
                    ->label(__('admin.service.fields.status')),
                TextColumn::make('created_at')
                    ->label(__('admin.service.fields.created_at'))
                    ->dateTime(),
                TextColumn::make('updated_at')
                    ->label(__('admin.service.fields.updated_at'))
                    ->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label(__('admin.common.action.view'))
                        ->tooltip(__('admin.common.tooltip.view'))
                        ->icon('heroicon-o-eye'),

                    EditAction::make()
                        ->label(__('admin.common.action.edit'))
                        ->tooltip(__('admin.common.tooltip.edit'))
                        ->icon('heroicon-o-pencil-square'),

                    DeleteAction::make()
                        ->label(__('admin.common.action.delete'))
                        ->tooltip(__('admin.common.tooltip.delete'))
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.common.modal.delete_title'))
                        ->modalDescription(__('admin.common.modal.delete_confirm'))
                        ->modalSubmitActionLabel(__('admin.common.action.confirm_delete'))
                        ->visible(fn($record) => ! $record->trashed()),

                    RestoreAction::make()
                        ->label(__('admin.common.action.restore'))
                        ->tooltip(__('admin.common.tooltip.restore'))
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn($record) => $record->trashed()),
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
            ])
            ->poll('3s');
    }
}
