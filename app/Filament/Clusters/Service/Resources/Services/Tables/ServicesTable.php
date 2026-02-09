<?php

namespace App\Filament\Clusters\Service\Resources\Services\Tables;

use App\Enums\UserRole;
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
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin.common.table.id'))
                    ->limit(50),
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
                ToggleColumn::make('is_active')
                    ->label(__('admin.service.fields.status')),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label(__('admin.common.action.view'))
                        ->tooltip(__('admin.common.tooltip.view'))
                        ->icon('heroicon-o-eye')
                        ->modalCancelActionLabel(__('common.action.cancel'))
                        ->modalHeading(__('common.modal.view_title'))
                        ->modalSubmitAction(false)
                        ->modalFooterActions(function ($action) {
                            return [
                                $action->getModalCancelAction()
                                    ->label(__('common.action.close'))
                                    ->color('danger'),
                            ];
                        }),

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
                ]),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label(__('admin.service.fields.provider'))
                    ->relationship(
                        name: 'provider',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn($query) => $query
                            ->where('role', UserRole::KTV->value)
                            ->where('is_active', true)
                    )
                    ->searchable() // Bật tìm kiếm
                    ->preload(),
                SelectFilter::make('is_active')
                    ->options([
                        true => __('admin.common.status.active'),
                        false => __('admin.common.status.inactive'),
                    ])
                    ->label(__('admin.common.filter.status')),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(5)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('admin.common.action.delete'))
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.common.modal.delete_title'))
                        ->modalDescription(__('admin.common.modal.delete_confirm'))
                        ->modalSubmitActionLabel(__('admin.common.action.confirm_delete')),

                ]),
            ])
            ->poll('5m');
    }
}
