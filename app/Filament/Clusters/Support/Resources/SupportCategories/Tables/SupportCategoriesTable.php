<?php

namespace App\Filament\Clusters\Support\Resources\SupportCategories\Tables;

use App\Filament\Components\CommonActions;
use App\Models\SupportCategory;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class SupportCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position', 'asc')
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin.common.support_category.table.id'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('admin.common.support_category.table.name'))
                    ->searchable()
                    ->limit(40)
                    ->weight('medium'),
                TextColumn::make('description')
                    ->label(__('admin.common.support_category.table.description'))
                    ->limit(90)
                    ->wrap()
                    ->placeholder(__('common.empty')),
                TextColumn::make('position')
                    ->label(__('admin.common.support_category.table.position'))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('admin.common.support_category.table.status'))
                    ->boolean()
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label(__('admin.common.support_category.table.created_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin.common.support_category.table.updated_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label(__('admin.common.action.edit'))
                        ->icon('heroicon-o-pencil-square'),
                    CommonActions::deleteAction(),
                ]), 
            ])
            ->emptyStateHeading(__('admin.common.support_category.empty_state.heading'))
            ->emptyStateDescription(__('admin.common.support_category.empty_state.description'))
            ->filters([]);
    }
}
