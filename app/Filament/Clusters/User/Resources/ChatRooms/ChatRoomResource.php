<?php

namespace App\Filament\Clusters\User\Resources\ChatRooms;

use App\Filament\Clusters\User\Resources\ChatRooms\Pages\DetailChatRoom;
use App\Filament\Clusters\User\Resources\ChatRooms\Pages\ListChatRooms;
use App\Filament\Clusters\User\Resources\ChatRooms\Tables\ChatRoomsTable;
use App\Models\ChatRoom;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ChatRoomResource extends Resource
{
    protected static ?string $model = ChatRoom::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleLeftRight;

    public static function getModelLabel(): string
    {
        return __('admin.chat_room.label');
    }

    public static function getHeading(): string
    {
        return __('admin.chat_room.label');
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.user');
    }

    public static function table(Table $table): Table
    {
        return ChatRoomsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChatRooms::route('/'),
            'detail' => DetailChatRoom::route('/{record}/detail'),
        ];
    }
}
