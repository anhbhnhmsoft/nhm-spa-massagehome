<?php

namespace App\Filament\Clusters\Support\Resources\SupportTickets\Tables;

use App\Enums\Admin\AdminRole;
use App\Enums\SupportTicketStatus;
use App\Models\AdminUser;
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
                        ->where('role', AdminRole::EMPLOYEE->value)
                        
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
                    ->action(function ($record) {
                        $record->update(['status' => SupportTicketStatus::CLOSED]);
                    })
                    ->visible(fn ($record) => $record->statusEnum() !== SupportTicketStatus::CLOSED),
                EditAction::make()
                    ->label(__('common.action.edit')),
            ])
            ->bulkActions([])
            ->emptyStateHeading(__('admin.support_ticket.empty_state.heading'))
            ->emptyStateDescription(__('admin.support_ticket.empty_state.description'));
    }
}
