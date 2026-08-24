<?php

namespace App\Observers;

use App\Enums\BookingStatus;
use App\Models\CustomerCrmData;
use App\Models\ServiceBooking;
use Illuminate\Support\Carbon;

/**
 * Observer theo dõi sự kiện trên ServiceBooking để tự động hóa CRM.
 */
class ServiceBookingObserver
{
    /**
     * Lắng nghe sự kiện cập nhật trạng thái đơn hàng.
     */
    public function updated(ServiceBooking $booking): void
    {
        if ($booking->wasChanged('status')) {
            $statusValue = is_object($booking->status) ? $booking->status->value : (int) $booking->status;

            if ($statusValue === BookingStatus::COMPLETED->value) {
                $this->recalculateCustomerCrmData((string) $booking->user_id);
            }
        }
    }

    /**
     * Tính toán lại các chỉ số CRM của khách hàng.
     */
    private function recalculateCustomerCrmData(string $userId): void
    {
        $completedBookings = ServiceBooking::where('user_id', $userId)
            ->where('status', BookingStatus::COMPLETED->value);

        $totalSpent = (float) $completedBookings->sum('price');
        $bookingCount = $completedBookings->count();
        $aov = $bookingCount > 0 ? $totalSpent / $bookingCount : 0.0;

        $firstBookingAt = ServiceBooking::where('user_id', $userId)
            ->where('status', BookingStatus::COMPLETED->value)
            ->orderBy('created_at', 'asc')
            ->value('created_at');

        $lastBookingAt = ServiceBooking::where('user_id', $userId)
            ->where('status', BookingStatus::COMPLETED->value)
            ->orderBy('created_at', 'desc')
            ->value('created_at');

        // Lấy danh sách khung giờ thường booking
        $frequentBookingHours = ServiceBooking::where('user_id', $userId)
            ->where('status', BookingStatus::COMPLETED->value)
            ->pluck('created_at')
            ->map(fn ($time) => Carbon::parse($time)->format('H:00'))
            ->toArray();

        CustomerCrmData::updateOrCreate(
            ['user_id' => $userId],
            [
                'total_spent' => $totalSpent,
                'booking_count' => $bookingCount,
                'aov' => $aov,
                'first_booking_at' => $firstBookingAt,
                'last_booking_at' => $lastBookingAt,
                'frequent_booking_hours' => array_values(array_unique($frequentBookingHours)),
            ]
        );
    }
}
