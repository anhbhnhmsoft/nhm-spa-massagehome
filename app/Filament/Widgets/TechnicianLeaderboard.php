<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TechnicianLeaderboard extends BaseWidget
{
    public function getHeading(): string
    {
        return __('admin.dashboard.widgets.leaderboard.heading');
    }

    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        // Fetch KTVs
        return User::query()->where('role', UserRole::KTV->value);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label(__('admin.dashboard.widgets.leaderboard.columns.id'))
                ->sortable(),
            Tables\Columns\ImageColumn::make('profile.avatar_url')
                ->label(__('admin.dashboard.widgets.leaderboard.columns.avatar'))
                ->disk("public")
                ->circular(),
            Tables\Columns\TextColumn::make('name')
                ->label(__('admin.dashboard.widgets.leaderboard.columns.name'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('reviews_received_avg_rating')
                ->label(__('admin.dashboard.widgets.leaderboard.columns.rating'))
                ->avg('reviewsReceived', 'rating')
                ->formatStateUsing(fn($state) => number_format($state, 1)),
            // Level (Custom logic needed, placeholder for now)
            // Service Duration (Sum of bookings duration)
            Tables\Columns\TextColumn::make('service_duration')
                ->label(__('admin.dashboard.widgets.leaderboard.columns.service_duration'))
                ->getStateUsing(fn(User $record) => number_format($record->ktvBookings()->sum('duration') / 60, 1)),

            // Performance (Total Revenue)
            Tables\Columns\TextColumn::make('revenue')
                ->label(__('admin.dashboard.widgets.leaderboard.columns.revenue'))
                ->getStateUsing(fn(User $record) => number_format($record->ktvBookings()->sum('price')))
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->withSum('ktvBookings', 'price')->orderBy('ktv_bookings_sum_price', $direction);
                }),

            // Points (Wallet Balance or specific points)
            Tables\Columns\TextColumn::make('points')
                ->label(__('admin.dashboard.widgets.leaderboard.columns.points'))
                ->getStateUsing(fn(User $record) => $record->wallet?->balance ?? 0),
        ];
    }
}
