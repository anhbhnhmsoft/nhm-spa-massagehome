<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\ChatRoom;
use App\Models\Message;
use Illuminate\Database\Eloquent\Builder;

class ChatRoomRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return ChatRoom::class;
    }

    /**
     * Lấy danh sách phòng chat KTV
     *
     */
    public function queryRoomKTV(): Builder
    {
        return $this->model->query()
            // Lấy tin nhắn mới nhất trong phòng chat
            ->with('latestMessage')
            // Lấy thông tin khách hàng
            ->with(['customer', 'customer.profile'])
            // Lấy thời gian tin nhắn mới nhất trong phòng chat
            ->addSelect(['last_message_at' => Message::query()->select('created_at')
                ->whereColumn('room_id', 'chat_rooms.id')
                ->latest()
                ->take(1)
            ]);
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Lấy danh sách phòng chat KTV
        if (isset($filters['ktv_id'])) {
            $ktvId = $filters['ktv_id'];
            $query->where('ktv_id', $ktvId);
            // Lấy số tin nhắn chưa đọc của KTV trong phòng chat
            if (isset($filters['unread_count'])) {
                $query->withCount(['messages as unread_count' => function ($q) use ($ktvId) {
                    $q->where('sender_by', '!=', $ktvId)
                        ->whereNull('seen_at');
                }]);
            }
        }


        if (isset($filters['ktv_id'])) {
            $query->where('ktv_id', $filters['ktv_id']);
        }

        if (isset($filters['id'])) {
            $query->where('id', $filters['id']);
        }

        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder
    {
        $sortBy = $sortBy ?? 'id';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        switch ($sortBy) {
            case 'id':
                return $query->orderBy('id', $direction);
            case 'last_message_at':
                return $query->orderBy('last_message_at', $direction);
            default:
                return $query->orderBy($sortBy, $direction);
        }
    }
}


