<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\NotificationStatus;
use App\Models\MobileNotification;
use Illuminate\Database\Eloquent\Builder;

class NotificationRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return MobileNotification::class;
    }

    /**
     * Lấy query notifications của user
     */
    public function queryNotification(): Builder
    {
        return $this->model->query()->with(['user']);
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo user_id
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Lọc theo type
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Lọc theo nhiều status
        if (isset($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }

        // Lọc theo status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Lọc chỉ chưa đọc
        if (isset($filters['unread_only']) && $filters['unread_only']) {
            $query->where('status', '!=', \App\Enums\NotificationStatus::READ->value);
        }


        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder
    {
        $sortBy = $sortBy ?? 'created_at';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        return $query->orderBy($sortBy, $direction);
    }

    /**
     * Cập nhật status của notification
     * @param int $notificationId
     * @param NotificationStatus $status
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function setStatus(int $notificationId, NotificationStatus $status)
    {
        return $this->update($notificationId, [
            'status' => $status->value,
        ]);
    }
}

