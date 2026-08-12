<?php

namespace App\Filament\Clusters\Support\Resources\SupportTickets\Tables;

use App\Enums\Admin\AdminRole;
use App\Enums\SupportTicketStatus;
use App\Models\AdminUser;
use App\Services\SupportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Enums\Admin\AdminGate;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_message_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin.common.table.id'))
                    ->searchable(),
                TextColumn::make('customer.phone')
                    ->label(__('common.fields.customer_phone'))
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label(__('admin.support_ticket.fields.customer'))
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('category.name')
                    ->label(__('admin.support_ticket.fields.category'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('assignedStaff.name')
                    ->label(__('admin.support_ticket.fields.assigned_staff'))
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.support_ticket.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->statusEnum()->label())
                    ->color(fn ($record) => match ($record->statusEnum()) {
                        SupportTicketStatus::PENDING => 'gray',
                        SupportTicketStatus::ASSIGNED => 'info',
                        SupportTicketStatus::IN_PROGRESS => 'warning',
                        SupportTicketStatus::CLOSED => 'success',
                    }),
                TextColumn::make('last_message_at')
                    ->label(__('admin.support_ticket.fields.last_message_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('sla_breached_at')
                    ->label(__('admin.support_ticket.fields.sla'))
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $record->sla_breached_at ? __('admin.support_ticket.sla_status.breached') : ($record->sla_warning_at ? __('admin.support_ticket.sla_status.warning') : __('admin.support_ticket.sla_status.ok')))
                    ->tooltip(fn () => __('admin.support_ticket.fields.sla_hint'))
                    ->color(fn ($record) => $record->sla_breached_at ? 'danger' : ($record->sla_warning_at ? 'warning' : 'success')),
                TextColumn::make('created_at')
                    ->label(__('admin.common.table.created_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.support_ticket.fields.status'))
                    ->options(SupportTicketStatus::toOptions()),
                SelectFilter::make('assigned_staff_id')
                    ->label(__('admin.support_ticket.fields.assigned_staff'))
                    ->options(fn () => AdminUser::query()
                        ->where('role', AdminRole::CUSTOMER_SUPPORT->value)
                        
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
            ])
            ->recordActions([
                Action::make('markAsResolved')
                    ->label(__('common.action.mark_as_resolved'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\Select::make('close_reason')->label('Lý do')->required()->options([
                            'resolved' => 'Đã giải quyết',
                            'customer_no_response' => 'Khách không phản hồi',
                            'duplicate' => 'Trùng ticket',
                            'out_of_scope' => 'Ngoài phạm vi',
                        ]),
                        \Filament\Forms\Components\Textarea::make('close_note')->label('Ghi chú')->maxLength(2000),
                    ])
                    ->action(function ($record, array $data) {
                        app(SupportService::class)->adminCloseTicket((int) $record->id, (int) Auth::id(), $data['close_reason'], $data['close_note'] ?? null);
                    })
                    ->visible(fn ($record) => $record->statusEnum() !== SupportTicketStatus::CLOSED),
                Action::make('reopen')
                    ->label('Mở lại')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn ($record) => app(SupportService::class)->reopenTicket((int) $record->id, (int) Auth::id()))
                    ->visible(fn ($record) => $record->statusEnum() === SupportTicketStatus::CLOSED && Gate::allows(AdminGate::ALLOW_SUPER_ADMIN)),
                EditAction::make()
                    ->label(__('common.action.edit')),
            ])
            ->bulkActions([])
            ->emptyStateHeading(__('admin.support_ticket.empty_state.heading'))
            ->emptyStateDescription(__('admin.support_ticket.empty_state.description'));
    }
}
