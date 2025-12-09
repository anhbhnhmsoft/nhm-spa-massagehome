<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\ServiceBooking;
use Illuminate\Database\Eloquent\Builder;

class BookingRepository extends BaseRepository
{

    protected function getModel(): string
    {
        return ServiceBooking::class;
    }

    /**
     * Lấy danh sách đặt lịch
     * @return Builder
     */
    public function queryBooking(): Builder
    {
        return $this->model->query()
            ->with([
                'user',
                'service'
            ]);
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo trạng thái
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder
    {
        return $query;
    }
}
