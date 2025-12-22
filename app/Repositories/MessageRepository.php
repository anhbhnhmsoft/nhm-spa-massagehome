<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Message;
use Illuminate\Database\Eloquent\Builder;

class MessageRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Message::class;
    }

    public function queryByRoom(string|int $roomId): Builder
    {
        return $this->query()
            ->where('room_id', $roomId)
            ->orderBy('created_at', 'asc');
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['sender_by'])) {
            $query->where('sender_by', $filters['sender_by']);
        }

        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder
    {
        $sortBy = $sortBy ?? 'created_at';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        return $query->orderBy($sortBy, $direction);
    }
}


