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
     * @param array $optionId ID của option dịch vụ cần kiểm tra
     * @return array{
     *     category: Category,
     *     option: array{
     *         price: float,
     *         duration: int,
     *     },
     * }
     * @throws ServiceException
     */
    public function validateServiceBooking(int $categoryId, int $ktvId, array $optionIds): array
    {
        $service = $this->categoryRepository->getCategoryByIdAndKTVIdAndOptionId(
            id: $categoryId,
            ktvId: $ktvId,
            optionIds: $optionIds,
        );
        // Kiểm tra dịch vụ có tồn tại không
        if (!$service) {
            throw new ServiceException(
                message: __("booking.service.not_found")
            );
        }
        $options = $service->prices->map(function ($option) {
            return [
                'price' => $option->price,
                'duration' => $option->duration,
            ];
        });

        $totalPrice = $options->sum('price');
        $totalDuration = $options->sum('duration');
        return [
            'category' => $service,
            'options' => $options,
            'total_price' => $totalPrice,
            'total_duration' => $totalDuration,
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
//        $endTime = $startTime->copy()->addMinutes($duration + $breakTime);
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

                // 2. Xác định day_key của ngày book (Monday = 2 ... Sunday = 8)
                $currentDayKey = $startTime->dayOfWeek === 0 ? 8 : $startTime->dayOfWeek + 1;

                // 3. Xác định day_key của ngày HÔM QUA (để check ca đêm vắt sang)
                $yesterdayKey = $currentDayKey == 2 ? 8 : $currentDayKey - 1;

                // Format lịch làm việc của kỹ thuật viên
                $schedules = collect($schedule->working_schedule);
                $todayConfig = $schedules->firstWhere('day_key', $currentDayKey);
                $yesterdayConfig = $schedules->firstWhere('day_key', $yesterdayKey);
                $isValidTime = false;
                $isWorkingToday = false;

                // Check trong ngày đặt lịch
                if ($todayConfig && $todayConfig['active']) {
                    $isWorkingToday = true;
                    $start = Carbon::createFromTimeString($todayConfig['start_time']);
                    $end = Carbon::createFromTimeString($todayConfig['end_time']);

                    // Lưu ý: $startTime là object Carbon chứa thời gian khách book truyền vào từ request
                    $booking = Carbon::createFromTimeString($startTime->format('H:i'));
                    if ($start->lte($end)) {
                        // Ca làm bình thường trong ngày (VD: 08:00 - 22:00)
                        // Dùng between() của Carbon sẽ bao gồm cả dấu bằng (>= và <=)
                        if ($booking->between($start, $end)) {
                            $isValidTime = true;
                        }
                    } else {
                        // Ca làm xuyên đêm bắt đầu từ hôm nay (VD: 16:00 - 08:00 sáng mai)
                        // Khách book vào buổi tối hôm nay -> booking phải lớn hơn hoặc bằng giờ bắt đầu
                        if ($booking->gte($start)) {
                            $isValidTime = true;
                        }
                    }
                }

                // --- KIỂM TRA CA ĐÊM CỦA NGÀY HÔM QUA (Vắt sang sáng nay) ---
                // Chỉ kiểm tra nếu hôm nay chưa khớp giờ
                if (!$isValidTime && $yesterdayConfig && $yesterdayConfig['active']) {
                    $start = Carbon::createFromTimeString($yesterdayConfig['start_time']);
                    $end = Carbon::createFromTimeString($yesterdayConfig['end_time']);
                    // Nếu hôm qua là ca xuyên đêm
                    if ($start->gt($end)) {
                        $isWorkingToday = true;
                        // Khách book vào sáng sớm hôm nay -> booking phải nhỏ hơn hoặc bằng giờ kết thúc
                        if ($booking->lte($end)) {
                            $isValidTime = true;
                        }
                    }
                }

                if (!$isValidTime) {
                    // Phân biệt 2 loại lỗi: Nghỉ làm cả ngày vs Có làm nhưng sai giờ
                    if (!$isWorkingToday) {
                        throw new ServiceException(message: __("booking.ktv.not_working"));
                    } else {
                        throw new ServiceException(message: __("booking.book_time.not_working"));
                    }
                }

//
//                // Lấy ra ngày trong tuần của thời gian book (1-7)
//                // Nếu là thứ 8 (0) thì coi như là thứ 8 (8) để hợp với array key KTVConfigSchedules
//                $dayKey = $startTime->dayOfWeek === 0 ? 8 : $startTime->dayOfWeek + 1;
//                // Lấy ra cấu hình làm việc của ngày này
//                $dayConfig = collect($schedule->working_schedule)->firstWhere('day_key', $dayKey);
//                // Kiểm tra xem kỹ thuật viên có làm việc vào ngày này không
//                if (!$dayConfig || !$dayConfig['active']) {
//                    throw new ServiceException(message: __("booking.ktv.not_working"));
//                }
//                // Kiểm tra xem thời gian book có nằm trong khoảng làm việc của kỹ thuật viên không
//                $startTimeSchedule = Carbon::createFromTimeString($dayConfig['start_time']);
//                $endTimeSchedule = Carbon::createFromTimeString($dayConfig['end_time']);
//
//                // Lấy ra thời gian book chỉ gồm giờ phút (không có giây)
//                $timeOnly = Carbon::createFromTimeString($startTime->copy()->format('H:i'));
//
//                if (!$timeOnly->between($startTimeSchedule, $endTimeSchedule)) {
//                    throw new ServiceException(message: __("booking.book_time.not_working"));
//                }
            }
        }

        // Lấy ra tất cả các booking đang thực hiện trong ngày này của kỹ thuật viên
        $onBookingInDay = $this->bookingRepository->query()
            ->where('ktv_user_id', $ktvId)
            ->whereDate('booking_time', $startTime->toDateString())
            ->whereIn('status', [
                BookingStatus::ONGOING->value,
            ])
            ->orderBy('booking_time', 'asc')
            ->first();
        // Kiểm tra xem kỹ thuật viên có đang có booking nào trong khoảng thời gian này không
        if ($onBookingInDay) {
            // Thông tin booking
            $existingStart = Carbon::parse($onBookingInDay->start_time);
            $existingDuration = (int) $onBookingInDay->duration;

            // giờ làm dự kiến xong = giờ bắt đầu + thời gian làm dịch vụ
            $existingEndExpected  = $existingStart->copy()->addMinutes($existingDuration);

            if ($startTime->lt($existingEndExpected)) {
                $suggestedTime = $startTime->copy()->format('H:i');
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
