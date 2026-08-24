<?php

namespace App\Filament\Clusters\Service\Resources\ServiceRequests\Tables;

use App\Enums\ProposalStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UrgencyLevel;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\ServiceRequestService;
use Filament\Actions\Action;

use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ServiceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#ID')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label(__('admin.service_request.fields.customer'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('service.title')
                    ->label(__('admin.service_request.fields.service'))
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['vi'] ?? reset($state)) : $state)
                    ->searchable(),

                TextColumn::make('urgency_level')
                    ->label(__('admin.service_request.fields.urgency_level'))
                    ->badge()
                    ->color(fn (UrgencyLevel $state): string => match ($state) {
                        UrgencyLevel::NEED_NOW => 'danger',
                        UrgencyLevel::TODAY => 'warning',
                        UrgencyLevel::SCHEDULED => 'info',
                    })
                    ->formatStateUsing(fn (UrgencyLevel $state) => $state->label()),

                TextColumn::make('status')
                    ->label(__('admin.service_request.fields.status'))
                    ->badge()
                    ->color(fn (ServiceRequestStatus $state): string => match ($state) {
                        ServiceRequestStatus::NEW => 'gray',
                        ServiceRequestStatus::ASSIGNED => 'warning',
                        ServiceRequestStatus::SEARCHING_KTV => 'info',
                        ServiceRequestStatus::PROPOSAL_SENT, ServiceRequestStatus::WAITING_CUSTOMER_CONFIRM => 'primary',
                        ServiceRequestStatus::MATCHED => 'success',
                        ServiceRequestStatus::BOOKING_CREATED => 'success',
                        ServiceRequestStatus::CLOSED, ServiceRequestStatus::CANCELED => 'danger',
                    })
                    ->formatStateUsing(fn (ServiceRequestStatus $state) => $state->label()),

                TextColumn::make('cskh.name')
                    ->label(__('admin.service_request.fields.cskh'))
                    ->placeholder(__('admin.unassigned')),

                TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('urgency_level')
                    ->label(__('admin.service_request.fields.urgency_level'))
                    ->options(UrgencyLevel::toOptions()),

                SelectFilter::make('status')
                    ->label(__('admin.service_request.fields.status'))
                    ->options(ServiceRequestStatus::toOptions()),
            ])
            ->actions([
                Action::make('assign_me')
                    ->label(__('admin.service_request.fields.assign_me'))
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->visible(fn (ServiceRequest $record) => is_null($record->cskh_id))
                    ->action(function (ServiceRequest $record) {
                        $record->update([
                            'cskh_id' => Auth::id(),
                            'status' => ServiceRequestStatus::ASSIGNED,
                        ]);
                        Notification::make()
                            ->title(__('admin.notification.success.update_success'))
                            ->success()
                            ->send();
                    }),

                Action::make('propose_ktv')
                    ->label(__('admin.service_request.fields.propose_ktv'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->form([
                        Select::make('ktv_id')
                            ->label(__('admin.booking.fields.staff'))
                            ->options(
                                User::where('role', UserRole::KTV->value)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (ServiceRequest $record, array $data, ServiceRequestService $service) {
                        $result = $service->proposeKtvForRequest($record->id, $data['ktv_id'], Auth::id());
                        if ($result->isSuccess()) {
                            Notification::make()
                                ->title(__('admin.service_request.messages.propose_success'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title($result->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('create_booking')
                    ->label(__('admin.service_request.fields.create_booking'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ServiceRequest $record) => in_array($record->status, [ServiceRequestStatus::MATCHED, ServiceRequestStatus::WAITING_CUSTOMER_CONFIRM]))
                    ->action(function (ServiceRequest $record, ServiceRequestService $service) {
                        $acceptedProposal = $record->proposals()->where('status', ProposalStatus::CUSTOMER_ACCEPTED->value)->first()
                            ?? $record->proposals()->first();

                        if (!$acceptedProposal) {
                            Notification::make()
                                ->title(__('admin.service_request.messages.proposal_not_found'))
                                ->warning()
                                ->send();
                            return;
                        }

                        $result = $service->createBookingFromRequest($record, $acceptedProposal->ktv_id);
                        if ($result->isSuccess()) {
                            Notification::make()
                                ->title(__('admin.service_request.messages.booking_created_success'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title($result->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}
