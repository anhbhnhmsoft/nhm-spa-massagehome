<?php

namespace App\Http\Resources\Chat;

class MessageSenderResource extends MessageResource
{

    public function __construct($resource, $receiverId)
    {
        parent::__construct($resource);
        $this->receiverId = $receiverId;
    }

    public function toArray($request): array
    {
        $data = parent::toArray($request);
        $data['receiver_id'] = $this->receiverId;
        return $data;
    }
}
