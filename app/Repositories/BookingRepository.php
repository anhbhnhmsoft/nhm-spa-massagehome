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
                'user.profile',
                'service',
                'ktvUser',
                'ktvUser.profile',
            ]);
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo trạng thái
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
            // Lọc theo số lượng đánh giá
            if (isset($filters['count_reviews_by_this_user_id']) && $filters['count_reviews_by_this_user_id'] === true) {
                $query->withCount(['reviews' => function (Builder $query) use ($filters) {
                    $query->where('review_by', $filters['user_id']);
                }]);
            }
        }

        if (isset($filters['ktv_user_id'])) {
            $query->where('ktv_user_id', $filters['ktv_user_id']);
        }


        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder
    {
        return $query;
    }
}
