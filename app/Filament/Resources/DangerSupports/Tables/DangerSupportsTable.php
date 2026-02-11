<?php

namespace App\Filament\Resources\DangerSupports\Tables;

use App\Enums\DangerSupportStatus;
use App\Filament\Clusters\Service\Resources\Bookings\BookingResource;
use App\Models\DangerSupport;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DangerSupportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('dashboard.danger_support_table.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('dashboard.danger_support_table.user'))
                    ->description(fn(DangerSupport $record) => $record->user->phone)
                    ->searchable(),
                TextColumn::make('latitude')
                    ->label(__('dashboard.danger_support_table.address'))
                    ->formatStateUsing(function () {
                        return __('dashboard.danger_support_table.view_on_map');
                    })
                    ->icon(Heroicon::MapPin)
                    ->url(fn(DangerSupport $record) => "https://www.google.com/maps/search/?api=1&query={$record->latitude},{$record->longitude}")
                    ->openUrlInNewTab(),
                TextColumn::make('booking.id')
                    ->label(__('dashboard.danger_support_table.booking'))
                    ->searchable()
                    ->url(fn(DangerSupport $record) => $record->booking_id ? BookingResource::getUrl('view', ['record' => $record->booking_id]) : null)
                    ->openUrlInNewTab()
                    ->placeholder(__('dashboard.danger_support_table.no_booking'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('dashboard.danger_support_table.status'))
                    ->formatStateUsing(fn($record) => $record->status->getLabel())
                    ->badge()
                    ->color(fn($record) => $record->status->getColor()),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label(__('dashboard.danger_support_table.confirm'))
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('dashboard.danger_support_table.confirm_modal_heading'))
                    ->modalDescription(__('dashboard.danger_support_table.confirm_modal_description'))
                    ->visible(fn(DangerSupport $record) => $record->status === DangerSupportStatus::PENDING)
                    ->action(function (DangerSupport $record) {
                        $record->update(['status' => DangerSupportStatus::CONFIRMED]);
                        Notification::make()
                            ->title(__('dashboard.danger_support_table.confirm_success'))
                            ->success()
                            ->send();
                    })
                    ->modalSubmitActionLabel(__('common.action.confirm'))
                    ->modalCancelActionLabel(__('common.action.cancel')),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
