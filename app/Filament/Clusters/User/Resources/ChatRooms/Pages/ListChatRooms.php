<?php

namespace App\Filament\Clusters\User\Resources\ChatRooms\Pages;

use App\Filament\Clusters\User\Resources\ChatRooms\ChatRoomResource;
use Filament\Resources\Pages\ListRecords;

class ListChatRooms extends ListRecords
{
    protected static string $resource = ChatRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
