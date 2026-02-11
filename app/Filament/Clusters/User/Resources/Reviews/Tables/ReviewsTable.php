<?php

namespace App\Filament\Clusters\User\Resources\Reviews\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('reviewer.name')
                    ->label(__('admin.review.fields.reviewer'))
                    ->description(fn ($record) => "ID: {$record->reviewer->id}")
                    ->searchable(),
                TextColumn::make('recipient.name')
                    ->label(__('admin.review.fields.provider'))
                    ->description(fn ($record) => "ID: {$record->recipient->id}")
                    ->searchable(),
                TextColumn::make('rating')
                    ->label(__('admin.review.fields.rating'))
                    ->sortable()
                    ->html() // bắt buộc để render HTML
                    ->formatStateUsing(function ($state) {
                        $max = 5;
                        return collect(range(1, $max))
                            ->map(fn ($i) => $i <= $state
                                ? '<span style="color:#facc15; font-size: 20px;">★</span>'
                                : '<span style="color:#e5e7eb; font-size: 20px;">★</span>'
                            )
                            ->implode('');
                    }),
                TextColumn::make('comment')
                    ->label(__('admin.review.fields.comment'))
                    ->limit(50),
                TextColumn::make('hidden')
                    ->label(__('admin.review.fields.hidden'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.common.yes') : __('admin.common.no'))
                    ->color(fn ($state) => $state ? 'danger' : 'success'),
                TextColumn::make('created_at')
                    ->label(__('admin.review.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('5m');
    }
}
