<?php

namespace App\Filament\Clusters\User\Resources\Reviews\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reviewer.name')
                    ->label(__('admin.review.fields.reviewer'))
                    ->searchable(),
                TextColumn::make('recipient.name')
                    ->label(__('admin.review.fields.provider'))
                    ->searchable(),
                TextColumn::make('rating')
                    ->label(__('admin.review.fields.rating'))
                    ->sortable(),
                TextColumn::make('comment')
                    ->label(__('admin.review.fields.comment'))
                    ->limit(50),
                ToggleColumn::make('hidden')
                    ->label(__('admin.review.fields.hidden')),
                TextColumn::make('created_at')
                    ->label(__('admin.review.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
