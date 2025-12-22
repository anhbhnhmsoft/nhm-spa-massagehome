<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $sender = $this->sender;
        return [
            'id' => (string) $this->id,
            'room_id' => (string) $this->room_id,
            'content' => $this->content,
            'sender_id' => (string) $sender->id,
            'sender_name' => $sender->name, // Thêm tên để hiển thị trên Socket
            'created_at' => $this->created_at,
            'seen_at' => $this->seen_at ?? null,
            'temp_id' => $this->temp_id,
        ];
    }
}


