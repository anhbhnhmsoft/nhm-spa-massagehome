<?php

namespace App\Filament\Clusters\KTV\Resources\KTVs\Tables;

use App\Enums\Gender;
use App\Filament\Clusters\KTV\Resources\KTVs\KTVResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class KTVsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->label(__('admin.common.table.name')),
                // TextColumn::make('email')
                //     ->searchable()
                //     ->label(__('admin.common.table.email')),
                ImageColumn::make('profile.avatar_url')
                    ->label(__('admin.common.table.avatar'))
                    ->disk('public')
                    ->defaultImageUrl(url('/images/avatar-default.svg')),
                TextColumn::make('phone')
                    ->searchable()
                    ->label(__('admin.common.table.phone')),
                TextColumn::make('address')
                    ->searchable()
                    ->limit(100)
                    ->label(__('admin.common.table.address')),
                TextColumn::make('profile.date_of_birth')
                    ->searchable()
                    ->label(__('admin.common.table.date_of_birth'))
                    ->date(),
                TextColumn::make('profile.gender')
                    ->label(__('admin.common.table.gender'))
                    ->formatStateUsing(fn($state) => Gender::getLabel($state)),
                IconColumn::make('is_online')
                    ->boolean()
                    ->label(__('admin.common.table.is_online')),
                TextColumn::make('created_at')
                    ->label(__('admin.common.table.created_at'))
                    ->dateTime(),
                TextColumn::make('updated_at')
                    ->label(__('admin.common.table.updated_at'))
                    ->dateTime(),
                TextColumn::make('deleted_at')
                    ->label(__('admin.common.table.deleted_at'))
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        ->action(fn(KTVResource $resource, $record) => $resource->getRecordViewForm($record))
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
                SelectFilter::make('profile.gender')
                    ->options(Gender::toOptions())
                    ->label(__('admin.common.filter.gender')),
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
