<?php

namespace App\Filament\Widgets;

use App\Enums\DangerSupportStatus;
use App\Filament\Clusters\Service\Resources\Bookings\BookingResource;
use App\Models\DangerSupport;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;

class DangerSupportTable extends BaseWidget
{
    protected int | string | array $columnSpan = 5;
    public static function title(): string
    {
        return __('dashboard.danger_support_table.title');
    }

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('dashboard.danger_support_table.title'))
            ->emptyStateHeading(__('dashboard.danger_support_table.empty_title'))
            ->query(
                DangerSupport::query()->latest()
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('dashboard.danger_support_table.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('dashboard.danger_support_table.user'))
                    ->description(fn(DangerSupport $record) => $record->user->phone)
                    ->searchable(),
                TextColumn::make('address')
                    ->label(__('dashboard.danger_support_table.address'))
                    ->url(fn(DangerSupport $record) => "https://www.google.com/maps/search/?api=1&query={$record->latitude},{$record->longitude}")
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-map-pin'),
                TextColumn::make('content')
                    ->label(__('dashboard.danger_support_table.content'))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('booking.id')
                    ->label(__('dashboard.danger_support_table.booking'))
                    ->searchable()
                    ->url(fn(DangerSupport $record) => $record->booking_id ? BookingResource::getUrl('view', ['record' => $record->booking_id]) : '#')
                    ->openUrlInNewTab()
                    ->badge(),
                TextColumn::make('booking.start_time')
                    ->label(__('dashboard.danger_support_table.booking_start_time'))
                    ->dateTime()
                    ->placeholder(__('dashboard.danger_support_table.no_booking')),
                TextColumn::make('status')
                    ->label(__('dashboard.danger_support_table.status'))
                    ->formatStateUsing(fn($record) => $record->status->getLabel())
                    ->badge()
                    ->color(fn($record) => $record->status->getColor()),
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
            ->poll('10s');
    }
}
