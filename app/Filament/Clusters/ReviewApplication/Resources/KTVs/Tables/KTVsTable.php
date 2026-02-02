<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Tables;

use App\Enums\Gender;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
use App\Filament\Clusters\Service\Resources\Services\ServiceResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\HtmlString;


class KTVsTable
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
                    ->description(function ($record) {
                        if ($record->reviewApplication->is_leader) {
                            return __('admin.ktv_apply.fields.is_leader');
                        }
                        return null;
                    })
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
                    Action::make('edit')
                        ->label(__('admin.common.action.detail'))
                        ->url(fn($record): string => KTVResource::getUrl('edit', ['record' => $record]))
                        ->icon('heroicon-o-identification'),
                    Action::make('view_service')
                        ->hidden(fn($record) => $record->reviewApplication->status !== ReviewApplicationStatus::APPROVED)
                        ->label(__('admin.common.action.view_service'))
                        ->icon('heroicon-o-inbox-stack')
                        ->url(fn($record): string => ServiceResource::getUrl('index', ['filters[user_id][value]' => $record->id])),
                    Action::make('view')
                        ->hidden(fn($record) => $record->reviewApplication->status !== ReviewApplicationStatus::APPROVED)
                        ->label(__('admin.common.action.ktv_dashboard'))
                        ->url(fn($record): string => KTVResource::getUrl('view', ['record' => $record]))
                        ->icon(Heroicon::ChartBar),
                    Action::make('qr_affiliate')
                        ->label(__('admin.common.affiliate_qr'))
                        ->icon('heroicon-o-qr-code')
                        ->modalHeading(__('admin.common.affiliate_qr'))
                        ->modalSubmitAction(false) // Ẩn nút Submit vì chỉ để xem
                        ->modalWidth('sm')
                        ->schema([
                            TextEntry::make('qr_code_placeholder')
                                ->hiddenLabel()
                                ->state(function ($record) {
                                    $url = route('affiliate.link', ['referrerId' => $record->id]);
                                    $qrUrl = "https://quickchart.io/qr?text=" . urlencode($url) . "&size=250";
                                    return new HtmlString("
                                        <div class='flex flex-col items-center justify-center space-y-4'>
                                              <img src='{$qrUrl}' alt='QR Code' class='w-64 h-64'>
                                            <div class='text-center text-sm font-mono text-gray-500 break-all'>
                                                {$url}
                                            </div>
                                        </div>
                                    ");
                                })
                        ]),
                    DeleteAction::make()
                        ->label(__('admin.common.action.delete'))
                        ->tooltip(__('admin.common.tooltip.delete'))
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.common.modal.delete_title'))
                        ->modalDescription(__('admin.common.modal.delete_confirm'))
                        ->modalSubmitActionLabel(__('admin.common.action.confirm_delete'))
                ]),
            ])
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
            ->emptyStateHeading(__('admin.ktv.empty_state.heading'))
            ->defaultSort('reviewApplication.status', 'asc');
    }
}
