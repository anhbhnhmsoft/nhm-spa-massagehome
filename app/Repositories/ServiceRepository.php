<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\BookingStatus;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;

class ServiceRepository extends BaseRepository
{
    public function getModel(): string
    {
        return Service::class;
    }

    public function queryService(): Builder
    {
        return $this->model->query()
            ->with([
                'category' => function ($query) {
                    $query->select(['id', 'name']);
                },
                'provider' => function ($query) {
                    $query->select(['id', 'name']);
                },
                'options',
            ])
            ->withCount([
                // Đếm số lượng booking đã hoàn thành
                'bookings' => function ($query) {
                    $query->where('status', BookingStatus::COMPLETED->value);
                }
            ]);
    }


    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo danh mục
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        // Lọc theo trạng thái
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        return $query;
    }

    /**
     * Sắp xếp query theo cột và hướng
     * @param Builder $query
     * @param string|null $sortBy
     * @param string $direction
     * @return Builder
     */
    public function sortQuery(Builder $query, ?string $sortBy, string $direction = 'desc'): Builder
    {
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }
        if (empty($column)) {
            $column = 'created_at';
        }
        $query->orderBy($column, $direction);
        return $query;
    }

}
