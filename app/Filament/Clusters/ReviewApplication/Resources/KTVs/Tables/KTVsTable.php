<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Tables;

use App\Enums\Gender;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
use App\Filament\Components\CommonActions;
use App\Filament\Components\CommonFields;
use App\Services\ProvinceService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                TextColumn::make('reviewApplication.nickname')
                    ->searchable()
                    ->label(__('admin.ktv_apply.fields.nickname')),
                TextColumn::make('phone')
                    ->searchable()
                    ->label(__('admin.common.table.phone')),
                TextColumn::make('profile.gender')
                    ->label(__('admin.common.table.gender'))
                    ->formatStateUsing(fn($state) => Gender::getLabel($state)),
                TextColumn::make('work_province')
                    ->label(__('admin.ktv_apply.fields.work_province'))
                    ->getStateUsing(fn ($record) => $record->reviewApplication?->work_province ?? $record->work_province)
                    ->placeholder('—')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('work_wards')
                    ->label(__('admin.ktv_apply.fields.work_wards'))
                    ->getStateUsing(function ($record) {
                        $province = $record->reviewApplication?->work_province ?? $record->work_province;
                        if (empty($province)) {
                            return null;
                        }
                        $wards = $record->reviewApplication?->work_wards ?? $record->work_wards ?? $record->ward;
                        if (is_array($wards)) {
                            return $wards[0] ?? null;
                        }
                        if (is_string($wards)) {
                            $decoded = json_decode($wards, true);
                            if (is_array($decoded)) {
                                return $decoded[0] ?? null;
                            }
                            return $wards;
                        }
                        return null;
                    })
                    ->placeholder('—')
                    ->searchable(),
                IconColumn::make('reviewApplication.portrait_verified')
                    ->label(__('admin.ktv_apply.fields.portrait_verified'))
                    ->boolean()
                    ->alignCenter(),
                TextColumn::make('reviewApplication.status')
                    ->label(__('admin.common.table.status_review'))
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => $state?->label())
                    ->color(fn($state) => $state?->color())
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('edit_inline')
                    ->label(__('admin.common.action.edit'))
                    ->url(fn($record): string => KTVResource::getUrl('edit', ['record' => $record]))
                    ->icon(Heroicon::PencilSquare),
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
                Filter::make('location')
                    ->form([
                        Select::make('work_province')
                            ->label(__('admin.ktv_apply.fields.work_province'))
                            ->searchable()
                            ->options(ProvinceService::toOptions())
                            ->placeholder(__('common.placeholder.all'))
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('work_ward', null)),
                        Select::make('work_ward')
                            ->label(__('admin.ktv_apply.fields.work_wards'))
                            ->searchable()
                            ->disabled(fn ($get) => blank($get('work_province')))
                            ->placeholder(fn ($get) => blank($get('work_province')) ? __('admin.ktv_apply.fields.select_province_first') : __('common.placeholder.all'))
                            ->options(function ($get) {
                                $province = $get('work_province');
                                if (blank($province)) {
                                    return [];
                                }
                                return ProvinceService::getWardsByProvince($province);
                            }),
                    ])
                    ->columns(2)
                    ->columnSpan(2)
                    ->query(function (Builder $query, array $data) {
                        $province = $data['work_province'] ?? null;
                        $ward = $data['work_ward'] ?? null;

                        if (!empty($province)) {
                            $query->where(function ($q) use ($province, $ward) {
                                $q->where(function ($sub) use ($province) {
                                    $sub->where('work_province', $province)
                                        ->orWhere('province', $province)
                                        ->orWhereHas('reviewApplication', function ($appQ) use ($province) {
                                            $appQ->where('work_province', $province);
                                        });
                                });

                                if (!empty($ward)) {
                                    $cleanWardName = preg_replace('/\s*\(.*?\)\s*$/', '', $ward);
                                    $q->where(function ($wq) use ($ward, $cleanWardName) {
                                        $wq->where('ward', 'ILIKE', "%{$cleanWardName}%")
                                            ->orWhere('work_wards', 'ILIKE', "%{$cleanWardName}%")
                                            ->orWhereJsonContains('work_wards', $ward)
                                            ->orWhereJsonContains('work_wards', $cleanWardName)
                                            ->orWhereHas('reviewApplication', function ($appQ) use ($ward, $cleanWardName) {
                                                $appQ->where('work_wards', 'ILIKE', "%{$cleanWardName}%")
                                                    ->orWhereJsonContains('work_wards', $ward)
                                                    ->orWhereJsonContains('work_wards', $cleanWardName);
                                            });
                                    });
                                }
                            });
                        }
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['work_province'])) {
                            $indicators[] = __('admin.ktv_apply.fields.work_province') . ': ' . $data['work_province'];
                        }
                        if (!empty($data['work_ward'])) {
                            $indicators[] = __('admin.ktv_apply.fields.work_wards') . ': ' . $data['work_ward'];
                        }
                        return $indicators;
                    }),
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
                SelectFilter::make('portrait_verified')
                    ->label(__('admin.ktv_apply.fields.portrait_verified'))
                    ->options([
                        true => __('admin.common.yes'),
                        false => __('admin.common.no'),
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        return $query->whereHas('reviewApplication', function ($q) use ($data) {
                            $q->where('portrait_verified', $data['value']);
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
            ->filtersFormColumns(4)
            ->deferFilters(false)

            ->emptyStateHeading(__('admin.ktv.empty_state.heading'))
            ->defaultSort('reviewApplication.status', 'asc');
    }
}
