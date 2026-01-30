<?php

namespace App\Filament\Clusters\User\Resources\Customers\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.customer.fields.name'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('admin.customer.fields.phone'))
                    ->searchable(),
                TextColumn::make('wallet.balance')
                    ->label(__('admin.customer.fields.balance'))
                    ->money()
                    ->default(0),
                TextColumn::make('created_at')
                    ->label(__('admin.customer.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
            ])
            ->poll('5m');
    }
}
