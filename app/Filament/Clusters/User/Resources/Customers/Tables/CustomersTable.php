<?php

namespace App\Filament\Clusters\User\Resources\Customers\Tables;

use App\Enums\CustomerRank;
use App\Enums\DemandStatus;
use App\Filament\Clusters\User\Resources\Customers\CustomerResource;
use App\Filament\Components\CommonActions;
use App\Models\AdminUser;
use App\Models\Province;
use App\Models\User;
use App\Services\ProvinceService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
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
                    ->sortable()
                    ->label(__('admin.customer.fields.id'))
                    ->toggleable(),
                ImageColumn::make('profile.avatar_url')
                    ->label(__('admin.customer.fields.avatar'))
                    ->disk('public')
                    ->defaultImageUrl(url('/images/avatar-default.svg'))
                    ->circular()
                    ->toggleable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('admin.customer.fields.name'))
                    ->toggleable(),
                TextColumn::make('email')
                    ->searchable()
                    ->label(__('admin.customer.fields.email'))
                    ->toggleable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->label(__('admin.customer.fields.phone'))
                    ->toggleable(),
                TextColumn::make('province')
                    ->label(__('admin.customer.fields.province'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('ward')
                    ->label(__('admin.customer.fields.ward'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('crmData.customer_rank')
                    ->label(__('admin.customer.fields.customer_rank'))
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state instanceof CustomerRank ? $state->label() : ($state ? CustomerRank::tryFrom($state)?->label() ?? $state : null))
                    ->color(fn ($state) => $state instanceof CustomerRank ? $state->color() : ($state ? CustomerRank::tryFrom($state)?->color() : null))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('crmData.demand_status')
                    ->label(__('admin.customer.fields.demand_status'))
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state instanceof DemandStatus ? $state->label() : ($state ? DemandStatus::tryFrom($state)?->label() ?? $state : null))
                    ->color(fn ($state) => $state instanceof DemandStatus ? $state->color() : ($state ? DemandStatus::tryFrom($state)?->color() : null))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('crmData.assignedCskh.name')
                    ->label(__('admin.customer.fields.assigned_cskh'))
                    ->placeholder(__('admin.customer.fields.unassigned'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('crmData.total_spent')
                    ->label(__('admin.customer.fields.total_spent'))
                    ->money('VND')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('crmData.booking_count')
                    ->label(__('admin.customer.fields.booking_count'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('crmData.cskh_notes')
                    ->label(__('admin.customer.fields.cskh_notes'))
                    ->limit(35)
                    ->placeholder('—')
                    ->tooltip(fn ($state) => $state ?: __('admin.customer.fields.cskh_notes'))
                    ->toggleable()
                    ->action(
                        Action::make('edit_cskh_note_column')
                            ->label(__('admin.customer.fields.cskh_notes'))
                            ->modalHeading(__('admin.customer.fields.cskh_notes'))
                            ->form([
                                Textarea::make('cskh_notes')
                                    ->label(__('admin.customer.fields.cskh_notes'))
                                    ->rows(5)
                                    ->default(fn (User $record) => $record->crmData?->cskh_notes)
                                    ->helperText(__('admin.customer.fields.cskh_notes_helper')),
                            ])
                            ->action(function (User $record, array $data): void {
                                $crm = $record->crmData()->firstOrCreate([]);
                                $crm->update([
                                    'cskh_notes' => $data['cskh_notes'] ?? null,
                                    'cskh_note' => $data['cskh_notes'] ?? null,
                                ]);
                                Notification::make()
                                    ->title(__('admin.customer.fields.cskh_notes'))
                                    ->body(__('common.success.update'))
                                    ->success()
                                    ->send();
                            })
                    ),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label(__('admin.customer.fields.created_at'))
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('edit_inline')
                    ->label(__('admin.common.action.edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (User $record): string => CustomerResource::getUrl('edit', ['record' => $record])),
                ActionGroup::make([
                    Action::make('edit')
                        ->label(__('admin.common.action.detail'))
                        ->icon('heroicon-o-identification')
                        ->url(fn (User $record): string => CustomerResource::getUrl('edit', ['record' => $record])),

                    Action::make('view_bookings')
                        ->label(__('admin.customer.section.booking_history'))
                        ->icon('heroicon-o-calendar-days')
                        ->url(fn (User $record): string => CustomerResource::getUrl('edit', ['record' => $record])),

                    CommonActions::qrAffiliateAction(),
                    CommonActions::deleteAction(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferFilters(false)
            ->filtersFormColumns(2)
            ->filters([
                Filter::make('location')
                    ->form([
                        Select::make('province')
                            ->label(__('admin.customer.fields.province'))
                            ->searchable()
                            ->options(ProvinceService::toOptions())
                            ->placeholder(__('common.placeholder.all'))
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('ward', null)),
                        Select::make('ward')
                            ->label(__('admin.customer.fields.ward'))
                            ->searchable()
                            ->disabled(fn ($get) => blank($get('province')))
                            ->placeholder(__('common.placeholder.all'))
                            ->options(function ($get) {
                                $province = $get('province');
                                if (blank($province)) {
                                    return [];
                                }
                                return ProvinceService::getWardsByProvince($province);
                            }),
                    ])
                    ->columns(2)
                    ->columnSpan(2)
                    ->query(function (Builder $query, array $data) {
                        $province = $data['province'] ?? null;
                        $ward = $data['ward'] ?? null;

                        if (!empty($province)) {
                            $query->where(function ($q) use ($province, $ward) {
                                $q->where(function ($sub) use ($province) {
                                    $sub->where('province', $province)
                                        ->orWhereHas('profile', function ($pq) use ($province) {
                                            $pq->where('province', $province);
                                        });
                                });

                                if (!empty($ward)) {
                                    $cleanWardName = preg_replace('/\s*\(.*?\)\s*$/', '', $ward);
                                    $q->where(function ($wq) use ($ward, $cleanWardName) {
                                        $wq->where('ward', 'ILIKE', "%{$cleanWardName}%")
                                            ->orWhereHas('profile', function ($pq) use ($ward, $cleanWardName) {
                                                $pq->where('ward', 'ILIKE', "%{$cleanWardName}%");
                                            });
                                    });
                                }
                            });
                        }
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['province'])) {
                            $indicators[] = __('admin.customer.fields.province') . ': ' . $data['province'];
                        }
                        if (!empty($data['ward'])) {
                            $indicators[] = __('admin.customer.fields.ward') . ': ' . $data['ward'];
                        }
                        return $indicators;
                    }),
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
                    ->searchable()
                    ->options(fn () => AdminUser::pluck('name', 'id')->toArray())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $val) => $q->whereHas('crmData', fn ($cq) => $cq->where('assigned_cskh_id', $val))
                    )),
            ])
            ->poll('5m');
    }
}
