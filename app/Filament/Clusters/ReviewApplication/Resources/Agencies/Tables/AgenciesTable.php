<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\Agencies\Tables;

use App\Enums\Gender;
use App\Enums\ReviewApplicationStatus;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\AgencyResource;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
use App\Filament\Components\CommonActions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AgenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
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
                    ->searchable()
                    ->label(__('admin.common.table.name')),
                TextColumn::make('reviewApplication.status')
                    ->label(__('admin.common.table.status_review'))
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => $state?->label())
                    ->color(fn($state) => $state?->color())
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->label(__('admin.common.table.phone')),
                TextColumn::make('profile.gender')
                    ->label(__('admin.common.table.gender'))
                    ->formatStateUsing(fn($state) => Gender::getLabel($state)),
                TextColumn::make('reviewApplication.address')
                    ->searchable()
                    ->label(__('admin.common.table.address')),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    // Xem chi tiết + chỉnh sửa
                    EditAction::make('edit')
                        ->label(__('admin.common.action.detail'))
                        ->icon('heroicon-o-identification'),
                    // Xem dashboard đại lý
                    ViewAction::make('view')
                        ->label(__('admin.common.action.agency_dashboard'))
                        ->icon(Heroicon::ChartBar),

                    // Hiển QR code giới thiệu affiliate
                    CommonActions::qrAffiliateAction(),

                    DeleteAction::make('delete')
                        ->label(__('admin.common.action.delete'))
                        ->tooltip(__('admin.common.tooltip.delete'))
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.common.modal.delete_title'))
                        ->modalDescription(__('admin.common.modal.delete_confirm'))
                        ->modalSubmitActionLabel(__('admin.common.action.confirm_delete'))
                ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(5)
            ->filters([
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
                SelectFilter::make('is_active')
                    ->options([
                        true => __('admin.common.status.active'),
                        false => __('admin.common.status.inactive'),
                    ])
                    ->label(__('admin.common.filter.status')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('admin.common.action.delete'))
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.common.modal.delete_title'))
                        ->modalDescription(__('admin.common.modal.delete_confirm'))
                        ->modalSubmitActionLabel(__('admin.common.action.confirm_delete')),
                ]),
            ])
            ->defaultSort('reviewApplication.status', 'asc')
            ->poll('5m');
    }
}
