<?php

namespace App\Filament\Clusters\Service\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
use App\Filament\Clusters\User\Resources\Customers\CustomerResource;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin.common.table.id'))
                    ->searchable(),
                TextColumn::make('ktv_display_name')
                    ->label(__('admin.booking.fields.ktv_user'))
                    ->getStateUsing(function ($record) {
                        if ($record->ktvUser) {
                            return $record->ktvUser->reviewApplication?->nickname ?: $record->ktvUser->name;
                        }
                        return $record->ktv_name ?: '-';
                    })
                    ->description(fn ($record) => $record->ktvUser?->phone ?? $record->ktv_phone ?? null)
                    ->url(fn ($record): ?string =>
                        $record->ktv_user_id && $record->ktvUser
                            ? KTVResource::getUrl('edit', ['record' => $record->ktv_user_id])
                            : null
                    )
                    ->openUrlInNewTab()
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('ktvUser', fn ($q) => $q->where('name', 'ILIKE', "%{$search}%")->orWhere('phone', 'ILIKE', "%{$search}%"))
                              ->orWhere('ktv_name', 'ILIKE', "%{$search}%")
                              ->orWhere('ktv_phone', 'ILIKE', "%{$search}%");
                    }),
                TextColumn::make('user_display_name')
                    ->label(__('admin.booking.fields.user'))
                    ->getStateUsing(function ($record) {
                        if ($record->user) {
                            return $record->user->name;
                        }
                        return $record->customer_name ?: '-';
                    })
                    ->description(fn ($record) => $record->user?->phone ?? $record->customer_phone ?? null)
                    ->url(fn ($record): ?string =>
                        $record->user_id && $record->user
                            ? CustomerResource::getUrl('edit', ['record' => $record->user_id])
                            : null
                    )
                    ->openUrlInNewTab()
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('user', fn ($q) => $q->where('name', 'ILIKE', "%{$search}%")->orWhere('phone', 'ILIKE', "%{$search}%"))
                              ->orWhere('customer_name', 'ILIKE', "%{$search}%")
                              ->orWhere('customer_phone', 'ILIKE', "%{$search}%");
                    }),
                TextColumn::make('service_display_name')
                    ->label(__('admin.booking.fields.service'))
                    ->getStateUsing(function ($record) {
                        return $record->service?->name ?? $record->service_name ?? '-';
                    })
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('service', fn ($q) => $q->where('name', 'ILIKE', "%{$search}%"))
                              ->orWhere('service_name', 'ILIKE', "%{$search}%");
                    }),
                TextColumn::make('booking_time')
                    ->sortable()
                    ->label(__('admin.booking.fields.time_range')) // Nhãn chung: Thời gian
                    ->alignCenter()
                    ->formatStateUsing(function ($record) {
                        if (!$record->start_time || !$record->end_time) return '-';
                        $start = Carbon::parse($record->start_time)->format('H:i');
                        $end = Carbon::parse($record->end_time)->format('H:i');
                        // Hiển thị chính: 14:00 - 15:00
                        return "{$start} - {$end}";
                    })
                    ->description(function ($record) {
                        $date = Carbon::parse($record->booking_time)->format('d/m/Y');
                        $duration = $record->duration;
                        return "{$date} ({$duration} min)";
                    })
                    ->color('primary') // Làm nổi bật mốc giờ
                    ->weight('bold'),  // Chữ đậm cho mốc giờ
                TextColumn::make('status')
                    ->label(__('admin.booking.fields.status'))
                    ->badge()
                    ->color(fn($state): string => BookingStatus::getColor($state))
                    ->formatStateUsing(fn($state) => BookingStatus::getLabel($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.booking.fields.status'))
                    ->options(BookingStatus::toOptions()),
                Filter::make('booking_time')
                    ->label(__('admin.booking.fields.booking_time'))
                    ->columns(2)
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('admin.common.filter.from_date')) // Ví dụ: Từ ngày
                            ->placeholder('DD/MM/YYYY'),
                        DatePicker::make('until')
                            ->label(__('admin.common.filter.to_date'))   // Ví dụ: Đến ngày
                            ->placeholder('DD/MM/YYYY'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn($query, $date) => $query->whereDate('booking_time', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn($query, $date) => $query->whereDate('booking_time', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = __('admin.common.filter.from') . ' ' . Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = __('admin.common.filter.to') . ' ' . Carbon::parse($data['until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(5)
            ->defaultSort('created_at', 'desc')
            ->poll('5m');
    }
}
