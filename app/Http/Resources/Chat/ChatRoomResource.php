<?php

namespace App\Http\Resources\Chat;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatRoomResource extends JsonResource
{

    /**
     * @var User|null
     */
    private mixed $partner;

    public function __construct($resource, $partner = null)
    {
        parent::__construct($resource);
        $this->partner = $partner;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'partner_id' => $this->partner->id,
            'partner_name' => $this->partner->name,
            'can_send' => (bool) $this->has_active_booking,
            'chat_state' => $this->chat_state,
            'closed_reason' => $this->closed_reason,
        ];
    }
}

