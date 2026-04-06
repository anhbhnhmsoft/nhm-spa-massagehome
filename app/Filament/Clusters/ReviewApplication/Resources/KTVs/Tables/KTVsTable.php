<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Tables;

use App\Enums\Admin\AdminGate;
use App\Enums\Gender;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
use App\Filament\Components\CommonActions;
use App\Filament\Components\CommonFields;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Facades\Gate;


class KTVsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                CommonFields::IdColumn(),
                ImageColumn::make('profile.avatar_url')
                    ->label(__('admin.common.table.avatar'))
                    ->width('80px')
                    ->disk('public')
                    ->alignCenter()
                    ->defaultImageUrl(url('/images/avatar-default.svg')),
                TextColumn::make('name')
                    ->description(function ($record) {
                        if ($record->reviewApplication->is_leader) {
                            return __('admin.ktv_apply.fields.is_leader');
                        }
                        return null;
                    })
                    ->searchable()
                    ->label(__('admin.common.table.name')),
                TextColumn::make('phone')
                    ->searchable()
                    ->label(__('admin.common.table.phone')),
                TextColumn::make('profile.gender')
                    ->label(__('admin.common.table.gender'))
                    ->formatStateUsing(fn($state) => Gender::getLabel($state)),
                TextColumn::make('reviewApplication.status')
                    ->label(__('admin.common.table.status_review'))
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => $state?->label())
                    ->color(fn($state) => $state?->color())
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    // Xem chi tiết + chỉnh sửa
                    Action::make('edit')
                        ->label(__('admin.common.action.detail'))
                        ->url(fn($record): string => KTVResource::getUrl('edit', ['record' => $record]))
                        ->icon('heroicon-o-identification'),

                    // Xem dashboard KTV
                    Action::make('view')
                        ->visible(fn($record) => $record->reviewApplication->status === ReviewApplicationStatus::APPROVED)
                        ->label(__('admin.common.action.ktv_dashboard'))
                        ->url(fn($record): string => KTVResource::getUrl('view', ['record' => $record]))
                        ->icon(Heroicon::ChartBar),

                     // Xem dịch vụ của KTV
                    CommonActions::viewServiceAction(),

                    // Hiển QR code giới thiệu affiliate
                    CommonActions::qrAffiliateAction(),

                    // đánh giá ảo
                    CommonActions::reviewVirtualAction(),

                    // Cập nhật số lượng dịch vụ đã thực hiện (buff ảo)
                    CommonActions::buffServiceAction(),

                    // Xóa KTV
                    CommonActions::deleteAction(),
                ]),
            ])
            ->filters([
                SelectFilter::make('is_leader')
                    ->label(__('admin.common.filter.is_leader'))
                    ->options([
                        true => __('admin.common.yes'),
                        false => __('admin.common.no'),
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        return $query->whereHas('reviewApplication', function ($q) use ($data) {
                            $q->where('is_leader', $data['value']);
                        });
                    }),
                SelectFilter::make('review_status')
                    ->label(__('admin.common.filter.review_status'))
                    ->options(ReviewApplicationStatus::toOptions())
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        return $query->whereHas('reviewApplication', function ($q) use ($data) {
                            $q->where('status', $data['value']);
                        });
                    }),
                SelectFilter::make('reviewApplication.referrer_id')
                    ->label(__('admin.ktv_apply.fields.agency'))
                    ->relationship(
                        name: 'referrer',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn($query) => $query
                            ->whereIn('role', [UserRole::AGENCY->value, UserRole::KTV->value])
                            ->where('is_active', true)
                    )
                    ->searchable()
                    ->preload()
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        return $query->whereHas('reviewApplication', function ($q) use ($data) {
                            $q->where('referrer_id', $data['value']);
                        });
                    }),
                SelectFilter::make('profile.gender')
                    ->options(Gender::toOptions())
                    ->label(__('admin.common.filter.gender')),
                SelectFilter::make('is_active')
                    ->label(__('admin.common.filter.status'))
                    ->options([
                        true => __('admin.common.status.active'),
                        false => __('admin.common.status.inactive'),
                    ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(5)

            ->emptyStateHeading(__('admin.ktv.empty_state.heading'))
            ->defaultSort('reviewApplication.status', 'asc');
    }
}
