<?php

namespace App\Filament\Clusters\KTV\Resources\KTVApplies\Tables;

use App\Enums\Gender;
use App\Enums\ReviewApplicationStatus;
use App\Filament\Clusters\KTV\Resources\KTVs\KTVResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class KTVAppliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->label(__('admin.common.table.name'))
                    ->searchable()
                    ->sortable(),
                ImageColumn::make('profile.avatar_url')
                    ->label(__('admin.common.table.avatar'))
                    ->disk('public')
                    ->defaultImageUrl(url('/images/avatar-default.svg')),

                TextColumn::make('phone')
                    ->label(__('admin.common.table.phone'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('profile.gender')
                    ->label(__('admin.common.table.gender'))
                    ->formatStateUsing(fn($state) => Gender::from($state)->label())
                    ->sortable(),

                TextColumn::make('profile.date_of_birth')
                    ->label(__('admin.common.table.date_of_birth'))
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('reviewApplication.province.name')
                    ->label(__('admin.ktv_apply.fields.province'))
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('reviewApplication.experience')
                    ->label(__('admin.ktv_apply.fields.experience'))
                    ->formatStateUsing(fn($state) => $state ? $state . ' ' . __('admin.ktv_apply.fields.years') : __('admin.common.empty'))
                    ->sortable(),

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
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('reviewApplication.status')
                    ->label(__('admin.common.table.status'))
                    ->options(ReviewApplicationStatus::toOptions())
                    ->attribute('reviewApplication.status'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->poll('5s');
    }
}
