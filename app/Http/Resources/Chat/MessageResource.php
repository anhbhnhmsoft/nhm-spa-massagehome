<?php

namespace App\Http\Resources\Chat;

use App\Core\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $sender = $this->sender;
        $senderProfile = $sender->profile;
        return [
            'id' => (string) $this->id,
            'room_id' => (string) $this->room_id,
            'content' => $this->content,
            'sender_id' => (string) $sender->id,
            'sender_name' => $sender->name, // Thêm tên để hiển thị trên Socket
            'sender_avatar' => $senderProfile->avatar_url ? Helper::getPublicUrl($senderProfile->avatar_url) : null, // Thêm avatar để hiển thị trên Socket
            'created_at' => $this->created_at,
            'seen_at' => $this->seen_at ?? null,
            'temp_id' => $this->temp_id,
        ];
    }
}


