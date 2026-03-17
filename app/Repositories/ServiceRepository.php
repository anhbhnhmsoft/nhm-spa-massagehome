<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\BookingStatus;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;

class ServiceRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Service::class;
    }

    /**
     * Lấy danh sách dịch vụ của KTV
     * @param $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getListServiceByUserId($userId)
    {
        $data = $this->query()
            ->with(['category' => function ($query) {
                $query->select('id', 'name', 'description', 'is_active');
                $query->where('is_active', true);
            }])
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();
        return $data;
    }

    /**
     * Tăng số lần thực hiện dịch vụ
     * @param int $userId
     * @param int $serviceId
     * @param int $amount
     * @return int
     */
    public function incrementPerformedCount(
        int $userId,
        int $serviceId,
        int $amount = 1,
    )
    {
        return $this->query()
            ->where('id', $serviceId)
            ->where('user_id', $userId)
            ->increment('performed_count', $amount);
    }

}
