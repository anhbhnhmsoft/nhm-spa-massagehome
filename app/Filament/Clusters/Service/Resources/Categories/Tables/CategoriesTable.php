<?php

namespace App\Filament\Clusters\Service\Resources\Categories\Tables;

use App\Filament\Components\CommonActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->label(__('admin.common.table.name')),
                ImageColumn::make('image_url')
                    ->label(__('admin.common.table.image'))
                    ->disk('public')
                    ->default('images/product_default.jpg'),
                TextColumn::make('description')
                    ->limit(100)
                    ->wrap(true)
                    ->searchable()
                    ->label(__('admin.common.table.description')),
                TextColumn::make('position')
                    ->label(__('admin.common.table.position')),
                TextColumn::make('created_at')
                    ->label(__('admin.common.table.created_at'))
                    ->dateTime(),
                IconColumn::make('is_featured')
                    ->boolean()
                    ->label(__('admin.common.table.is_featured')),
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
                TextColumn::make('usage_count')
                    ->label(__('admin.common.table.usage_count')),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label(__('admin.common.action.edit'))
                        ->tooltip(__('admin.common.tooltip.edit'))
                        ->icon('heroicon-o-pencil-square'),

                   CommonActions::deleteAction()
                ]),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->options([
                        true => __('admin.common.status.active'),
                        false => __('admin.common.status.inactive'),
                    ])
                    ->label(__('admin.common.filter.status')),
                SelectFilter::make('is_featured')
                    ->options([
                        true => __('admin.common.status.active'),
                        false => __('admin.common.status.inactive'),
                    ])
                    ->label(__('admin.common.filter.is_featured')),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
