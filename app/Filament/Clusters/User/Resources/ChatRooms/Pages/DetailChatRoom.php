<?php

namespace App\Filament\Clusters\User\Resources\ChatRooms\Pages;

use App\Filament\Clusters\User\Resources\ChatRooms\ChatRoomResource;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Contracts\Support\Htmlable;

class DetailChatRoom extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ChatRoomResource::class;

    protected string $view = 'filament.pages.detail-chat-room';

    public function getTitle(): string | Htmlable
    {
        return __('admin.chat_room.fields.chat_room');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.chat_room.fields.chat_room');
    }


    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }
    protected function getPollingInterval(): ?string
    {
        return '5s';
    }
    public function getMessagesProperty()
    {
        return $this->record->messages()->with('sender')->orderBy('created_at', 'asc')->get();
    }
}
