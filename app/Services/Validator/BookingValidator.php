<?php

namespace App\Services\Validator;

use App\Core\Service\ServiceException;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Repositories\BookingRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Carbon;

class BookingValidator
{
    public function __construct(
        protected UserRepository $userRepository,
        protected CategoryRepository $categoryRepository,
        protected BookingRepository $bookingRepository,
    )
    {

    }

    /**
     * Kiểm tra dịch vụ có phù hợp để book dịch vụ không
     * @param int $categoryId ID của danh mục dịch vụ cần kiểm tra
     * @param int $ktvId ID của KTV cần kiểm tra
     * @param int $optionId ID của option dịch vụ cần kiểm tra
     * @return array{
     *     category: Category,
     *     option: array{
     *         price: float,
     *         duration: int,
     *     },
     * }
     * @throws ServiceException
     */
    public function validateServiceBooking(int $categoryId, int $ktvId, int $optionId): array
    {
        $service = $this->categoryRepository->getCategoryByIdAndKTVIdAndOptionId(
            id: $categoryId,
            ktvId: $ktvId,
            optionId: $optionId,
        );
        // Kiểm tra dịch vụ có tồn tại không
        if (!$service) {
            throw new ServiceException(
                message: __("booking.service.not_found")
            );
        }
        $option = $service->prices->first();

        return [
            'category' => $service,
            'option' => [
                'price' => $option->price,
                'duration' => $option->duration,
            ],
        ];
    }



    /**
     * Kiểm tra KTV có thể đặt lịch trong khoảng thời gian này không
     * @param int $ktvId
     * @param int $duration - Thời gian làm dịch vụ
     * @param int $breakTime - Thời gian nghỉ giữa 2 lần phục vụ
     * @return array{
     *    break_time: int, // Thời gian nghỉ giữa 2 lần phục vụ,
     *    booking_time: Carbon // Thời gian book lịch (là hiện tại + break time),
     * }
     * @throws ServiceException
     */
    public function validateKtvAvailabilityToBooking(
        int $ktvId,
        int $duration,
        int $breakTime,
    ): array
    {
        // Thời gian hiện tại
        $now = Carbon::now();
        // Lấy thông tin kỹ thuật viên
        $ktv = $this->userRepository->queryUser()
            ->where('id', $ktvId)
            ->where('role', UserRole::KTV->value)
            ->with(['schedule'])
            ->first();
        if (!$ktv) {
            // Lỗi này bắt buộc throw vì không thể book lịch cho KTV không tồn tại
            throw new ServiceException(message: __("booking.ktv.not_found"));
        }

        // Thời gian bắt đầu đặt lịch dự kiến
        $startTime = $now->copy();

        // Thời gian kết thúc đặt lịch dự kiến  = Thời gian bắt đầu + Thời gian dịch vụ + Thời gian nghỉ
        $endTime = $startTime->copy()->addMinutes($duration + $breakTime);
        // Kiểm tra xem kỹ thuật viên có đang làm việc ko
        $schedule = $ktv->schedule;
        if ($schedule) {
            // Kiểm tra xem kỹ thuật viên có đang làm việc ko
            if (!$schedule->is_working) {
                throw new ServiceException(
                    message: __("booking.ktv.not_working")
                );
            }
            // Kiểm tra xem kỹ thuật viên có làm việc vào ngày này không
            if ($schedule->working_schedule) {
                // Lấy ra ngày trong tuần của thời gian book (1-7)
                // Nếu là thứ 8 (0) thì coi như là thứ 8 (8) để hợp với array key KTVConfigSchedules
                $dayKey = $startTime->dayOfWeek === 0 ? 8 : $startTime->dayOfWeek + 1;
                // Lấy ra cấu hình làm việc của ngày này
                $dayConfig = collect($schedule->working_schedule)->firstWhere('day_key', $dayKey);
                // Kiểm tra xem kỹ thuật viên có làm việc vào ngày này không
                if (!$dayConfig || !$dayConfig['active']) {
                    throw new ServiceException(message: __("booking.ktv.not_working"));
                }
                // Kiểm tra xem thời gian book có nằm trong khoảng làm việc của kỹ thuật viên không
                $startTimeSchedule = Carbon::createFromTimeString($dayConfig['start_time']);
                $endTimeSchedule = Carbon::createFromTimeString($dayConfig['end_time']);

                // Lấy ra thời gian book chỉ gồm giờ phút (không có giây)
                $timeOnly = Carbon::createFromTimeString($startTime->copy()->format('H:i'));

                if (!$timeOnly->between($startTimeSchedule, $endTimeSchedule)) {
                    throw new ServiceException(message: __("booking.book_time.not_working"));
                }
            }
        }

        // Lấy ra tất cả các booking trong ngày này của kỹ thuật viên
        $bookingsInDay = $this->bookingRepository->query()
            ->where('ktv_user_id', $ktvId)
            ->whereDate('booking_time', $startTime->toDateString())
            ->whereIn('status', [
                BookingStatus::CONFIRMED->value,
                BookingStatus::ONGOING->value,
                BookingStatus::PENDING->value
            ])
            // Sắp xếp theo thời gian book tăng dần
            ->orderBy('booking_time', 'asc')
            ->get();
        foreach ($bookingsInDay as $existing) {
            // Thông tin booking cũ (Booking B)
            $existingStart = Carbon::parse($existing->booking_time);
            $existingDuration = (int) $existing->duration;

            // Thời điểm kết thúc của B bao gồm cả thời gian nghỉ
            // End B = Start B + Duration B + BreakTime
            $existingEndWithBreak = $existingStart->copy()->addMinutes($existingDuration + $breakTime);
            /**
             * LOGIC KIỂM TRA TRÙNG:
             * Hai khoảng thời gian trùng nhau khi: (StartA < EndB) VÀ (EndA > StartB)
             * Ở đây End được tính kèm cả BreakTime để đảm bảo khoảng nghỉ.
             */
            $isOverlapping = $startTime->lt($existingEndWithBreak) && $endTime->gt($existingStart);
            if ($isOverlapping) {
                // Duyệt tiếp các booking sau đó để tìm xem khi nào KTV thực sự rảnh liên tục đủ thời gian của dịch vụ mới
                // Ở đây ta đơn giản hóa: Gợi ý là ngay sau khi booking này kết thúc
                $suggestedTime = $existingEndWithBreak->copy()->format('H:i');
                throw new ServiceException(
                    message: __("booking.book_time.overlapping", ['time' => $suggestedTime])
                );
            }
        }

        return [
            'break_time' => $breakTime,
            'booking_time' => $startTime->copy()->addMinutes($breakTime),
        ];
    }
}
