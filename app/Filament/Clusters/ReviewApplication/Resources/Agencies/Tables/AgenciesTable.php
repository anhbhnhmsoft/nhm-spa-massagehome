<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\Agencies\Tables;

use App\Enums\Gender;
use App\Enums\ReviewApplicationStatus;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\AgencyResource;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
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
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Image;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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
                    EditAction::make('edit')
                        ->label(__('admin.common.action.detail'))
                        ->icon('heroicon-o-identification'),
                    Action::make('view_service')
                        ->label(__('admin.common.action.view_ktv_manager_agency'))
                        ->icon(Heroicon::UserGroup)
                        ->url(fn ($record): string => KTVResource::getUrl('index', ['filters[reviewApplication][referrer_id][value]' => $record->id])),
                    ViewAction::make('view')
                        ->label(__('admin.common.action.agency_dashboard'))
                        ->icon(Heroicon::ChartBar),
                    Action::make('qr_affiliate')
                        ->label(__('admin.common.affiliate_qr'))
                        ->icon('heroicon-o-qr-code')
                        ->modalHeading(__('admin.common.affiliate_qr'))
                        ->modalSubmitAction(false) // Ẩn nút Submit vì chỉ để xem
                        ->modalWidth('sm')
                        ->modalCancelActionLabel(__('common.action.close'))
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
