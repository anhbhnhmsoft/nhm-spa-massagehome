<?php

namespace App\Observers;

use App\Enums\BookingStatus;
use App\Models\Category;
use App\Models\CustomerCrmData;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Observer theo dõi sự kiện trên ServiceBooking để tự động hóa CRM và Snapshot dữ liệu.
 */
class ServiceBookingObserver
{
    /**
     * Tự động lưu Snapshot thông tin KTV, Khách hàng và Dịch vụ trước khi lưu đơn.
     */
    public function saving(ServiceBooking $booking): void
    {
        // 1. Snapshot KTV nếu có ktv_user_id
        if ($booking->isDirty('ktv_user_id') || (empty($booking->ktv_name) && !empty($booking->ktv_user_id))) {
            if ($booking->ktv_user_id) {
                $ktv = User::with(['profile', 'reviewApplication'])->find($booking->ktv_user_id);
                if ($ktv) {
                    $booking->ktv_name = $ktv->reviewApplication?->nickname ?: $ktv->name;
                    $booking->ktv_phone = $ktv->phone;
                    $booking->ktv_avatar_url = $ktv->profile?->avatar_url;
                }
            }
        }

        // 2. Snapshot Khách hàng nếu có user_id
        if ($booking->isDirty('user_id') || (empty($booking->customer_name) && !empty($booking->user_id))) {
            if ($booking->user_id) {
                $customer = User::with('profile')->find($booking->user_id);
                if ($customer) {
                    $booking->customer_name = $customer->name;
                    $booking->customer_phone = $customer->phone;
                    $booking->customer_avatar_url = $customer->profile?->avatar_url;
                    $booking->customer_gender = $customer->profile?->gender;
                }
            }
        }

        // 3. Snapshot Dịch vụ nếu có category_id
        if ($booking->isDirty('category_id') || (empty($booking->service_name) && !empty($booking->category_id))) {
            if ($booking->category_id) {
                $category = Category::find($booking->category_id);
                if ($category) {
                    $booking->service_name = $category->name;
                }
            }
        }
    }

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
