<?php

namespace App\Http\Resources\Support;

use App\Enums\SupportMessageSenderType;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'room_id' => $this->room_id,
            'status' => method_exists($this->resource, 'statusEnum')
                ? $this->resource->statusEnum()->value
                : $this->status,
            'customer' => [
                'id' => (string) $this->customer_id,
                'name' => $this->customer?->name,
                'avatar' => $this->customer?->profile?->avatar_url,
            ],
            'assigned_staff' => $this->assignedStaff ? [
                'id' => (string) $this->assignedStaff->id,
                'name' => $this->assignedStaff->name,
            ] : null,
            'category' => $this->category ? [
                'id' => (string) $this->category->id,
                'name' => $this->category->getTranslations('name'),
                'description' => $this->category->getTranslations('description'),
                'position' => $this->category->position,
                'is_active' => $this->category->is_active,
            ] : null,
            'latest_booking' => $this->latestBooking ? [
                'id' => (string) $this->latestBooking->id,
                'booking_time' => $this->latestBooking->booking_time?->toISOString(),
                'status' => $this->latestBooking->status,
                'service_name' => $this->latestBooking->service?->name,
            ] : null,
            'last_message_at' => $this->last_message_at?->toISOString(),
            'unread_count' => $this->unreadCountFor($request),
            'latest_message' => $this->latestMessage ? [
                'id' => (string) $this->latestMessage->id,
                'support_ticket_id' => (string) $this->latestMessage->support_ticket_id,
                'content' => $this->latestMessage->content,
                'sender_type' => method_exists($this->latestMessage, 'senderTypeEnum')
                    ? $this->latestMessage->senderTypeEnum()->value
                    : $this->latestMessage->sender_type,
                'sender_user_id' => $this->latestMessage->sender_user_id ? (string) $this->latestMessage->sender_user_id : null,
                'sender_admin_id' => $this->latestMessage->sender_admin_id ? (string) $this->latestMessage->sender_admin_id : null,
                'temp_id' => $this->latestMessage->temp_id,
                'seen_at' => $this->latestMessage->seen_at?->toISOString(),
                'created_at' => $this->latestMessage->created_at?->toISOString(),
                'sender_name' => $this->latestMessage->senderTypeEnum() === SupportMessageSenderType::STAFF
                    ? $this->latestMessage->staff?->name
                    : $this->latestMessage->customer?->name,
                'sender_avatar' => $this->latestMessage->senderTypeEnum() === SupportMessageSenderType::STAFF
                    ? null
                    : $this->latestMessage->customer?->profile?->avatar_url,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function unreadCountFor(Request $request): int
    {
        $user = $request->user();
        $senderType = $user instanceof AdminUser
            ? SupportMessageSenderType::CUSTOMER
            : SupportMessageSenderType::STAFF;

        return $this->messages()
            ->where('sender_type', $senderType->dbValue())
            ->whereNull('seen_at')
            ->count();
    }
}
