<?php

namespace App\Filament\Clusters\Service\Resources\ProactiveInvites\Tables;

use App\Enums\InvitationStatus;
use App\Models\KtvProactiveInvite;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProactiveInvitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('ktv.name')
                    ->label(__('admin.proactive_invite.fields.ktv'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label(__('admin.proactive_invite.fields.customer'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('admin.proactive_invite.fields.status'))
                    ->formatStateUsing(fn ($state) => $state instanceof InvitationStatus ? $state->label() : InvitationStatus::getLabel($state))
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof InvitationStatus ? $state->value : (int)$state) {
                        InvitationStatus::PENDING->value => 'warning',
                        InvitationStatus::ACCEPTED->value => 'success',
                        InvitationStatus::DECLINED->value => 'danger',
                        InvitationStatus::EXPIRED->value => 'gray',
                        InvitationStatus::CANCELED_BY_ADMIN->value => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('note')
                    ->label(__('admin.proactive_invite.fields.note'))
                    ->limit(30),

                TextColumn::make('expires_at')
                    ->label(__('admin.proactive_invite.fields.expires_at'))
                    ->dateTime('H:i d/m/Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('admin.proactive_invite.fields.created_at'))
                    ->dateTime('H:i d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.proactive_invite.fields.status'))
                    ->options(InvitationStatus::toOptions()),
            ])
            ->actions([
                Action::make('cancel_invite')
                    ->label(__('admin.proactive_invite.fields.cancel_invite'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (KtvProactiveInvite $record) => $record->status === InvitationStatus::PENDING)
                    ->action(function (KtvProactiveInvite $record) {
                        $record->status = InvitationStatus::CANCELED_BY_ADMIN;
                        $record->save();

                        Notification::make()
                            ->title(__('admin.proactive_invite.messages.invite_canceled'))
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
