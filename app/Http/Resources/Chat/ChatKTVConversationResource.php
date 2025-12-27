<?php

namespace App\Http\Resources\Chat;

use App\Core\Helper;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatKTVConversationResource extends JsonResource
{
    public function toArray($request)
    {
        $customer = $this->customer;
        $customerProfile = $customer->profile;
        $lastMessage = $this->latestMessage ?? null;
        return [
            'id' => $this->id,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'avatar' => $customerProfile->avatar_url ? Helper::getPublicUrl($customerProfile->avatar_url) : null,
            ],
            'unread_count' => $this->unread_count,
            'latest_message' => $lastMessage ? [
                'id' => $lastMessage->id,
                'content' => $lastMessage->content,
                'created_at' => $lastMessage->created_at,
            ] : null,
        ];
    }
}
