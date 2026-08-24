<?php

namespace App\Filament\Clusters\User\Resources\Customers\Tables;

use App\Enums\CustomerRank;
use App\Enums\DemandStatus;
use App\Filament\Clusters\User\Resources\Customers\CustomerResource;
use App\Filament\Components\CommonActions;
use App\Models\AdminUser;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                TextColumn::make('crmData.customer_rank')
                    ->label('Hạng khách')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof CustomerRank ? $state->label() : ($state ? CustomerRank::getLabel((int)$state) : 'Standard'))
                    ->color(fn ($state) => match ($state instanceof CustomerRank ? $state->value : (int)$state) {
                        CustomerRank::VIP->value => 'success',
                        CustomerRank::GOLD->value => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('crmData.demand_status')
                    ->label('Trạng thái nhu cầu')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof DemandStatus ? $state->label() : ($state ? DemandStatus::getLabel((int)$state) : 'Đang tìm hiểu'))
                    ->color('info'),
                TextColumn::make('crmData.assignedCskh.name')
                    ->label('CSKH phụ trách')
                    ->default('Chưa phân công')
                    ->searchable(),
                TextColumn::make('crmData.total_spent')
                    ->label('Tổng chi tiêu')
                    ->money('VND')
                    ->sortable(),
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
                    CommonActions::giftCouponAction(),
                    CommonActions::qrAffiliateAction(),
                    CommonActions::deleteAction(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('customer_rank')
                    ->label(__('admin.customer.fields.customer_rank'))
                    ->options(CustomerRank::toOptions())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $val) => $q->whereHas('crmData', fn ($cq) => $cq->where('customer_rank', $val))
                    )),
                SelectFilter::make('demand_status')
                    ->label(__('admin.customer.fields.demand_status'))
                    ->options(DemandStatus::toOptions())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $val) => $q->whereHas('crmData', fn ($cq) => $cq->where('demand_status', $val))
                    )),
                SelectFilter::make('assigned_cskh_id')
                    ->label(__('admin.customer.fields.assigned_cskh'))
                    ->options(fn () => AdminUser::pluck('name', 'id')->toArray())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $val) => $q->whereHas('crmData', fn ($cq) => $cq->where('assigned_cskh_id', $val))
                    )),
            ])
            ->poll('5m');
    }
}
