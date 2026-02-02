<?php

namespace App\Filament\Clusters\User\Resources\Customers\Tables;

use App\Filament\Clusters\User\Resources\Customers\CustomerResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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
            ])
            ->poll('5m');
    }
}
