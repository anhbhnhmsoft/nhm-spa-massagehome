<?php

namespace App\Filament\Clusters\User\Resources\ChatRooms\Tables;

use App\Filament\Clusters\User\Resources\ChatRooms\ChatRoomResource;
use App\Models\ChatRoom;
use App\Models\Message;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChatRoomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('customer.profile.avatar_url')
                    ->label(__('admin.chat_room.fields.customer_avatar'))
                    ->circular()
                    ->disk('public'),
                TextColumn::make('customer.name')
                    ->label(__('admin.chat_room.fields.customer_name'))
                    ->description(fn(ChatRoom $record) => $record->customer->email ?? '')
                    ->searchable(),

                ImageColumn::make('ktv.profile.avatar_url')
                    ->label(__('admin.chat_room.fields.ktv_avatar'))
                    ->circular()
                    ->disk('public'),
                TextColumn::make('ktv.name')
                    ->label(__('admin.chat_room.fields.ktv_name'))
                    ->description(fn(ChatRoom $record) => $record->ktv->email ?? '')
                    ->searchable(),

                TextColumn::make('latestMessage.content')
                    ->label(__('admin.chat_room.fields.last_message'))
                    ->limit(40)
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label(__('admin.chat_room.fields.started'))
                    ->time('H:i - M d, Y')
                    ->sortable(),
            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderByRaw('(' . Message::query()
                        ->select('created_at')
                        ->whereColumn('room_id', 'chat_rooms.id')
                        ->orderByDesc('created_at')
                        ->orderByDesc('id')
                        ->limit(1)
                        ->toSql() . ') DESC NULLS LAST')
                    ->orderByDesc('created_at');
            })
            ->recordActions([
                ViewAction::make()->url(fn($record) => ChatRoomResource::getUrl('detail', ['record' => $record]))
                    ->label(__('common.action.view')),

            ]);
    }
}
