<?php

namespace App\Http\Resources\Support;

use App\Enums\SupportMessageSenderType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'support_ticket_id' => (string) $this->support_ticket_id,
            'content' => $this->content,
            'sender_type' => method_exists($this->resource, 'senderTypeEnum')
                ? $this->resource->senderTypeEnum()->value
                : $this->sender_type,
            'sender_user_id' => $this->sender_user_id ? (string) $this->sender_user_id : null,
            'sender_admin_id' => $this->sender_admin_id ? (string) $this->sender_admin_id : null,
            'temp_id' => $this->temp_id,
            'seen_at' => $this->seen_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'sender_name' => $this->senderTypeEnum() === SupportMessageSenderType::STAFF
                ? $this->staff?->name
                : $this->customer?->name,
            'sender_avatar' => $this->senderTypeEnum() === SupportMessageSenderType::STAFF
                ? null
                : $this->customer?->profile?->avatar_url,
        ];
    }
}
