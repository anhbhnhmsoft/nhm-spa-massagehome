<?php

namespace App\Filament\Clusters\Marketing\Resources\Coupons\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('admin.coupon.fields.code'))
                    ->searchable(),
                TextColumn::make('label')
                    ->label(__('admin.coupon.fields.label'))
                    ->searchable(),
                TextColumn::make('is_percentage')
                    ->label(__('admin.coupon.fields.type'))
                    ->formatStateUsing(fn($state) => $state ? __('admin.coupon.is_percentage.percent') : __('admin.coupon.is_percentage.fixed')),
                TextColumn::make('discount_value')
                    ->label(__('admin.coupon.fields.discount_value')),
                TextColumn::make('usage_limit')
                    ->label(__('admin.coupon.fields.usage_limit')),
                ToggleColumn::make('is_active')
                    ->label(__('admin.common.table.status'))
                    ->toggleable(),
                TextColumn::make('used_count')
                    ->label(__('admin.coupon.fields.used_count')),
                TextColumn::make('creator.name')
                    ->label(__('admin.common.table.creator')),
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
                SelectFilter::make('is_percentage')
                    ->options([
                        true => __('admin.coupon.is_percentage.percent'),
                        false => __('admin.coupon.is_percentage.fixed'),
                    ])
                    ->label(__('admin.common.filter.type')),
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
