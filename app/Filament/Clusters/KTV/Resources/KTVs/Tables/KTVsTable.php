<?php

namespace App\Filament\Clusters\KTV\Resources\KTVs\Tables;

use App\Enums\BookingStatus;
use App\Enums\Gender;
use App\Filament\Clusters\KTV\Resources\KTVs\KTVResource;
use App\Models\Service;
use App\Models\ServiceBooking;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class KTVsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->label(__('admin.common.table.name')),
                // TextColumn::make('email')
                //     ->searchable()
                //     ->label(__('admin.common.table.email')),
                ImageColumn::make('profile.avatar_url')
                    ->label(__('admin.common.table.avatar'))
                    ->disk('public')
                    ->defaultImageUrl(url('/images/avatar-default.svg')),
                TextColumn::make('phone')
                    ->searchable()
                    ->label(__('admin.common.table.phone')),
                TextColumn::make('profile.date_of_birth')
                    ->searchable()
                    ->label(__('admin.common.table.date_of_birth'))
                    ->date(),
                TextColumn::make('profile.gender')
                    ->label(__('admin.common.table.gender'))
                    ->formatStateUsing(fn($state) => Gender::getLabel($state)),
                TextColumn::make('created_at')
                    ->label(__('admin.common.table.created_at'))
                    ->dateTime(),
                TextColumn::make('deleted_at')
                    ->label(__('admin.common.table.deleted_at'))
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')
                    ->label(__('admin.common.table.status'))
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label(__('admin.common.action.view'))
                        ->tooltip(__('admin.common.tooltip.view'))
                        ->action(fn(KTVResource $resource, $record) => $resource->getRecordViewForm($record))
                        ->icon('heroicon-o-eye'),

                    EditAction::make()
                        ->label(__('admin.common.action.edit'))
                        ->tooltip(__('admin.common.tooltip.edit'))
                        ->icon('heroicon-o-pencil-square'),

                    Action::make('viewServices')
                        ->label(__('admin.ktv.action.view_services'))
                        ->tooltip(__('admin.ktv.tooltip.view_services'))
                        ->icon('heroicon-o-briefcase')
                        ->modalHeading(fn($record) => __('admin.ktv.modal.services_title', ['name' => $record->name]))
                        ->modalContent(function ($record) {
                            $services = Service::where('user_id', $record->id)->get();
                            $bookings = ServiceBooking::where('ktv_user_id', $record->id)
                                ->with(['service', 'user'])
                                ->orderBy('booking_time', 'desc')
                                ->get();

                            $servicesHtml = '<div class="mb-6"><h3 class="text-lg font-semibold mb-4">' . __('admin.ktv.modal.services_created') . '</h3>';
                            if ($services->isEmpty()) {
                                $servicesHtml .= '<p class="text-gray-500">' . __('admin.common.empty') . '</p>';
                            } else {
                                $servicesHtml .= '<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b"><th class="text-left p-2">' . __('admin.service.fields.name') . '</th><th class="text-left p-2">' . __('admin.common.table.status') . '</th><th class="text-left p-2">' . __('admin.common.table.created_at') . '</th></tr></thead><tbody>';
                                foreach ($services as $service) {
                                    $statusBadge = $service->is_active
                                        ? '<span class="px-2 py-1 bg-green-100 text-green-800 rounded">' . __('admin.common.status.active') . '</span>'
                                        : '<span class="px-2 py-1 bg-gray-100 text-gray-800 rounded">' . __('admin.common.status.inactive') . '</span>';
                                    $servicesHtml .= '<tr class="border-b"><td class="p-2">' . e($service->name ?? '-') . '</td><td class="p-2">' . $statusBadge . '</td><td class="p-2">' . ($service->created_at?->format('d/m/Y H:i') ?? '-') . '</td></tr>';
                                }
                                $servicesHtml .= '</tbody></table></div>';
                            }
                            $servicesHtml .= '</div>';

                            $bookingsHtml = '<div><h3 class="text-lg font-semibold mb-4">' . __('admin.ktv.modal.services_booked') . '</h3>';
                            if ($bookings->isEmpty()) {
                                $bookingsHtml .= '<p class="text-gray-500">' . __('admin.common.empty') . '</p>';
                            } else {
                                $bookingsHtml .= '<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b"><th class="text-left p-2">' . __('admin.service.fields.name') . '</th><th class="text-left p-2">' . __('admin.booking.fields.user') . '</th><th class="text-left p-2">' . __('admin.booking.fields.booking_time') . '</th><th class="text-left p-2">' . __('admin.common.table.status') . '</th><th class="text-left p-2">' . __('admin.booking.fields.price') . '</th></tr></thead><tbody>';
                                foreach ($bookings as $booking) {
                                    $statusValue = (int) ($booking->status ?? 0);
                                    $statusBadge = '<span class="px-2 py-1 rounded ' . match($statusValue) {
                                        BookingStatus::PENDING->value => 'bg-yellow-100 text-yellow-800',
                                        BookingStatus::CONFIRMED->value => 'bg-blue-100 text-blue-800',
                                        BookingStatus::ONGOING->value => 'bg-purple-100 text-purple-800',
                                        BookingStatus::COMPLETED->value => 'bg-green-100 text-green-800',
                                        BookingStatus::CANCELED->value => 'bg-red-100 text-red-800',
                                        BookingStatus::PAYMENT_FAILED->value => 'bg-orange-100 text-orange-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    } . '">' . BookingStatus::getLabel($statusValue) . '</span>';
                                    $bookingsHtml .= '<tr class="border-b"><td class="p-2">' . e($booking->service?->name ?? '-') . '</td><td class="p-2">' . e($booking->user?->name ?? '-') . '</td><td class="p-2">' . ($booking->booking_time?->format('d/m/Y H:i') ?? '-') . '</td><td class="p-2">' . $statusBadge . '</td><td class="p-2">' . number_format($booking->price ?? 0, 0, ',', '.') . ' đ</td></tr>';
                                }
                                $bookingsHtml .= '</tbody></table></div>';
                            }
                            $bookingsHtml .= '</div>';

                            return new HtmlString($servicesHtml . $bookingsHtml);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel(__('admin.common.action.close')),

                    DeleteAction::make()
                        ->label(__('admin.common.action.delete'))
                        ->tooltip(__('admin.common.tooltip.delete'))
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.common.modal.delete_title'))
                        ->modalDescription(__('admin.common.modal.delete_confirm'))
                        ->modalSubmitActionLabel(__('admin.common.action.confirm_delete'))
                        ->visible(fn($record) => ! $record->trashed()),

                    RestoreAction::make()
                        ->label(__('admin.common.action.restore'))
                        ->tooltip(__('admin.common.tooltip.restore'))
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn($record) => $record->trashed()),
                ]),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('profile.gender')
                    ->options(Gender::toOptions())
                    ->label(__('admin.common.filter.gender')),
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

                    RestoreBulkAction::make()
                        ->label(__('admin.common.action.restore'))
                        ->visible(fn($livewire) => $livewire->tableFilters['trashed']['value'] ?? null === 'only'),

                    ForceDeleteBulkAction::make()
                        ->label(__('admin.common.action.force_delete'))
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.common.modal.force_delete_title'))
                        ->modalDescription(__('admin.common.modal.force_delete_confirm'))
                        ->modalSubmitActionLabel(__('admin.common.action.confirm_delete')),
                ]),
            ])
            ->poll('5s');
    }
}
