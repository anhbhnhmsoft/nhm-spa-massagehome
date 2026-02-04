<?php

namespace App\Filament\Clusters\Marketing\Resources\MobileNotifications\Tables;

use App\Enums\NotificationStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MobileNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('admin.mobile_notification.fields.user'))
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('admin.mobile_notification.fields.title'))
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('admin.mobile_notification.fields.description'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.mobile_notification.fields.status'))
                    ->badge()
                    ->numeric()
                    ->formatStateUsing(function ($state) {
                        return NotificationStatus::from($state->value)->label();
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.mobile_notification.fields.created_at'))
                    ->dateTime()
                    ->sortable()
            ])
            ->defaultSort('created_at', 'asc');
    }
}
