<?php

namespace App\Filament\Clusters\User\Resources\ChatRooms\Tables;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\ChatRoom;
use App\Filament\Clusters\User\Resources\ChatRooms\ChatRoomResource;
use Filament\Actions\ViewAction;

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
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()->url(fn($record) => ChatRoomResource::getUrl('detail', ['record' => $record])),

            ]);
    }
}
