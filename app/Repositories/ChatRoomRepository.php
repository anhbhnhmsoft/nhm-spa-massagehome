<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\ChatRoom;
use Illuminate\Database\Eloquent\Builder;

class ChatRoomRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return ChatRoom::class;
    }

    public function queryRoom(): Builder
    {
        return $this->model->query()
            ->with(['customer', 'ktv']);
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
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

        return $query->orderBy($sortBy, $direction);
    }
}


