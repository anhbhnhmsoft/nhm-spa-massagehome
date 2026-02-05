<?php

namespace App\Filament\Clusters\Service\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
class BookingInfoList
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Thông tin chung
                Section::make(__('admin.booking.sections.general_info'))
                    ->columns(2)
                    ->compact()
                    ->schema([
                        TextEntry::make('id')
                            ->label(__('admin.common.table.id'))
                            ->copyable(),

                        TextEntry::make('status')
                            ->label(__('admin.booking.fields.status'))
                            ->badge()
                            ->color(fn ($state): string => BookingStatus::getColor($state))
                            ->formatStateUsing(fn($state) => BookingStatus::getLabel($state)),

                        TextEntry::make('user.name')
                            ->label(__('admin.booking.fields.user'))
                            ->color('primary')
                            ->weight('bold')
                            ->icon('heroicon-m-user'),

                        TextEntry::make('ktvUser.name')
                            ->label(__('admin.booking.fields.ktv_user'))
                            ->weight('bold')
                            ->icon('heroicon-m-identification'),

                        TextEntry::make('service.name')
                            ->label(__('admin.booking.fields.service'))
                            ->color('primary')
                            ->weight('bold'),
                        TextEntry::make('price')
                            ->label(__('admin.booking.fields.price'))
                            ->formatStateUsing(fn ($state) => number_format($state, 0, '.', ','))
                            ->suffix(' ' . __('admin.currency'))
                            ->icon('heroicon-m-currency-dollar'),
                        TextEntry::make('coupon.name')
                            ->icon('heroicon-m-tag')
                            ->hidden(fn ($record) => !$record->coupon_id)
                            ->label(__('admin.booking.fields.coupon')),
                        TextEntry::make('price_before_discount')
                            ->hidden(fn ($record) => !$record->coupon_id)
                            ->label(__('admin.booking.fields.price_before_discount'))
                            ->formatStateUsing(fn ($state) => number_format($state, 0, '.', ','))
                            ->suffix(' ' . __('admin.currency'))
                            ->icon('heroicon-m-currency-dollar'),
                    ]),

                // Thông tin chi tiết
                Section::make(__('admin.booking.sections.detail'))
                    ->columns(2)
                    ->compact()
                    ->schema([
                        TextEntry::make('booking_time')
                            ->label(__('admin.booking.fields.booking_time'))
                            ->dateTime("d-m-Y H:i")
                            ->icon('heroicon-m-clock'),
                        TextEntry::make('time_range')
                            ->label(__('admin.booking.fields.time_range'))
                            ->state(function ($record) {
                                $start = $record->start_time ? Carbon::parse($record->start_time)->format('H:i') : '-';
                                $end = $record->end_time ? Carbon::parse($record->end_time)->format('H:i') : '-';
                                return "{$start} - {$end} ({$record->duration} min)";
                            })
                            ->icon('heroicon-m-clock'),
                        TextEntry::make('address')
                            ->label(__('admin.booking.fields.address'))
                            ->icon('heroicon-m-map-pin')
                            ->suffixAction(
                                Action::make('open_map')
                                    ->label(__('admin.booking.actions.view_map'))
                                    ->icon('heroicon-o-map')
                                    ->color('primary')
                                    ->url(function ($record) {
                                        // Ưu tiên dùng tọa độ nếu có, nếu không thì dùng địa chỉ text
                                        if ($record->latitude && $record->longitude) {
                                            return "https://www.google.com/maps/search/?api=1&query={$record->latitude},{$record->longitude}";
                                        }
                                        return "https://www.google.com/maps/search/?api=1&query=" . urlencode($record->address);
                                    })
                                    ->openUrlInNewTab()
                            ),
                        TextEntry::make('note_address')
                            ->icon('heroicon-m-map-pin')
                            ->label(__('admin.booking.fields.note_address'))
                            ->placeholder(__('admin.common.empty')),
                        TextEntry::make('note')
                            ->label(__('admin.booking.fields.note'))
                            ->columnSpanFull()
                            ->placeholder(__('admin.common.empty')),

                        TextEntry::make('reason_cancel')
                            ->hidden(fn ($record) => $record->status !== BookingStatus::WAITING_CANCEL->value)
                            ->label(__('admin.booking.fields.reason_cancel'))
                            ->columnSpanFull()
                            ->color('danger')
                            ->weight('bold')
                            ->icon('heroicon-m-exclamation-triangle')
                            ->placeholder(__('admin.common.empty')),

                    ]),
            ]);
    }
}
