<?php

namespace App\Filament\Clusters\User\Resources\Customers\Tables;

use App\Filament\Clusters\User\Resources\Customers\CustomerResource;
use App\Filament\Components\CommonActions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('id')
                    ->searchable()
                    ->width('80px')
                    ->label(__('admin.common.table.id')),
                ImageColumn::make('profile.avatar_url')
                    ->label(__('admin.common.table.avatar'))
                    ->width('80px')
                    ->disk('public')
                    ->alignCenter()
                    ->defaultImageUrl(url('/images/avatar-default.svg')),
                TextColumn::make('name')
                    ->label(__('admin.common.table.name'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('admin.common.table.phone'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('admin.common.table.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('edit')
                        ->label(__('admin.common.action.detail'))
                        ->url(fn($record): string => CustomerResource::getUrl('edit', ['record' => $record]))
                        ->icon('heroicon-o-identification'),
                    CommonActions::qrAffiliateAction(),
                    CommonActions::deleteAction(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->poll('5m');
    }
}
