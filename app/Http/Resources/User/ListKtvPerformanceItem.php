<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListKtvPerformanceItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar_url,
            'phone' => $this->phone,
            'total_reviews' => $this->total_reviews,
            'total_finished_bookings' => $this->total_finished_bookings, // Số lượng đặt lịch đã hoàn thành
            'total_revenue' => $this->total_revenue, // Tổng doanh thu từ các đặt lịch đã hoàn thành
            'total_unique_customers' => $this->total_unique_customers, // Số lượng khách hàng duy nhất đã đặt lịch
        ];
    }
}
