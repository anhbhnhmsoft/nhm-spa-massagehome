<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\Jobs\WalletTransBookingCase;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Jobs\RefundBookingCancelJob;
use App\Jobs\SendNotificationJob;
use App\Models\CategoryPrice;
use App\Models\Service;
use App\Repositories\BookingRepository;
use App\Repositories\CategoryPriceRepository;
use App\Repositories\CouponRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\ServiceRepository;
use App\Enums\ConfigName;
use App\Enums\PaymentType;
use App\Jobs\WalletTransactionBookingJob;
use App\Repositories\ServiceOptionRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserReviewApplicationRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingService extends BaseService
{
    public function __construct(
        protected BookingRepository $bookingRepository,
        protected ServiceRepository $serviceRepository,
        protected CouponRepository $couponRepository,
        protected ServiceOptionRepository $serviceOptionRepository,
        protected CategoryPriceRepository $categoryPriceRepository,
        protected CouponService $couponService,
        protected WalletRepository $walletRepository,
        protected WalletTransactionRepository $walletTransactionRepository,
        protected UserReviewApplicationRepository $reviewApplicationRepository,
        protected UserRepository $userRepository,
        protected ConfigService $configService,
        protected WalletService $walletService,
        protected ReviewRepository $reviewRepository,
    ) {
        parent::__construct();
    }

    public function getBookingRepository(): BookingRepository
    {
        return $this->bookingRepository;
    }


    /**
     * Lấy danh sách lịch hẹn dịch vụ của người dùng
     * @param FilterDTO $dto
     * @return ServiceReturn
     */
    public function bookingPaginate(FilterDTO $dto): ServiceReturn
    {
        try {
            $query = $this->bookingRepository->queryBooking();

            $query = $this->bookingRepository->filterQuery(
                query: $query,
                filters: $dto->filters
            );
            $query = $this->bookingRepository->sortQuery(
                query: $query,
                sortBy: $dto->sortBy,
                direction: $dto->direction
            );
            $paginate = $query->paginate(
                perPage: $dto->perPage,
                page: $dto->page
            );
            return ServiceReturn::success(
                data: $paginate
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi ServiceService@bookingPaginate",
                ex: $exception
            );
            return ServiceReturn::success(
                data: new LengthAwarePaginator(
                    items: [],
                    total: 0,
                    perPage: $dto->perPage,
                    currentPage: $dto->page
                )
            );
        }
    }


    /**
     * Đặt lịch hẹn dịch vụ
     * @param int $serviceId
     * @param int $optionId
     * @param string $address
     * @param string $latitude
     * @param string $longitude
     * @param string $bookTime
     * @param string|null $note
     * @param string|null $noteAddress
     * @param int|null $couponId
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function bookService(
        int             $serviceId,
        int             $optionId,
        string          $address,
        string          $latitude,
        string          $longitude,
        string          $bookTime,
        ?string         $note = null,
        ?string         $noteAddress = null,
        ?int            $couponId = null,
    ): ServiceReturn {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            // Lấy thời gian book dịch vụ
            $currentBookingStartTime = Carbon::parse($bookTime);

            // Kiểm tra dịch vụ có phù hợp để book dịch vụ không
            $service = $this->checkServiceAllowBooking(
                serviceId: $serviceId,
            );

            // kiểm tra option dịch vụ có tồn tại không và thuộc về dịch vụ này
            $serviceOption = $this->checkOptionServiceAllowBooking(
                categoryId: $service->category_id,
                optionId: $optionId,
            );

            // Kiểm tra kỹ thuật viên có thể book dịch vụ lúc này không
            $this->checkKtvAvailabilityToBooking(
                ktvId: $service->user_id,
                startTime: $currentBookingStartTime,
                duration: $serviceOption->duration
            );

            // Tính toán giá cuối cùng bao gồm giảm giá nếu có
            $priceData = $this->calculateFinalPrice(
                serviceId: $serviceId,
                price: $serviceOption->price,
                couponId: $couponId,
            );

            // Lấy giá cuối cùng
            $finalPrice = $priceData['final_price'];
            // Lấy giá trước giảm giá
            $priceBeforeDiscount = $priceData['price_before_discount'];

            // Kiểm tra số dư ví của khách hàng có đủ không
            $walletUserCheck = $this->walletService->checkUserWalletBalance(
                userId: $user->id,
                price: $finalPrice,
            );
            if (!$walletUserCheck['is_enough']) {
                DB::commit();
                return ServiceReturn::success(
                    data: [
                        'status' => false,
                        'failed' => [
                            'not_enough_money' => true,
                            'final_price' => $finalPrice,
                            'balance_customer' => $walletUserCheck['balance']
                        ]
                    ],
                    message: __("booking.wallet.not_enough")
                );
            }

            // Tạo mới lịch hẹn
            // Phải để status là pending rồi bắn vào queue để xử lý thanh toán
            $booking = $this->bookingRepository->create([
                'user_id' => $user->id,
                'ktv_user_id' => $service->user_id,
                'service_id' => $serviceId,
                'coupon_id' => $couponId,
                'duration' => $serviceOption->duration,
                'booking_time' => $currentBookingStartTime,
                'start_time' => null,
                'end_time' => null,
                'status' => BookingStatus::PENDING->value,
                'price' => $finalPrice,
                'price_before_discount' => $priceBeforeDiscount,
                'payment_type' => PaymentType::BY_POINTS->value,
                'note' => $note ?? '',
                'address' => $address ?? '',
                'latitude' => $latitude ?? 0,
                'longitude' => $longitude ?? 0,
                'note_address' => $noteAddress ?? '',
            ]);

            DB::commit();

            // xử lý giao dịch, ghi lại lịch sử dùng coupon
            WalletTransactionBookingJob::dispatch(
                bookingId: $booking->id,
                case: WalletTransBookingCase::CONFIRM_BOOKING,
            );

            return ServiceReturn::success(
                data: [
                    'status' => true,
                    'success' => [
                        'booking_id' => $booking->id,
                    ]
                ]
            );
        }
        catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi ServiceService@bookService",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Hủy booking, trường hợp khách hàng chủ động hủy hoặc thất bại thanh toán
     * @param string $bookingId
     * @param string|null $reason
     * @return ServiceReturn
     */
    public function cancelBooking(string $bookingId, ?string $reason = null): ServiceReturn
    {
        try {
            $userCurrent = Auth::user();

            // Tìm booking cần hủy và khóa hàng để tránh xung đột
            $booking = $this->bookingRepository->query()
                ->lockForUpdate()
                ->find($bookingId);
            if (!$booking) {
                throw new ServiceException(
                    message: __("booking.not_found")
                );
            };
            // Kiểm tra trạng thái booking có thể hủy không
            if (!in_array($booking->status, BookingStatus::caseCanCancel())) {
                throw new ServiceException(
                    message: __("booking.not_permission")
                );
            }
            // Kiểm tra quyền hủy booking (chỉ có thể hủy booking của chính mình)
            if ($userCurrent->id != $booking->user_id && $userCurrent->id != $booking->ktv_user_id) {
                throw new ServiceException(
                    message: __("booking.not_permission")
                );
            }

            // Cập nhật trạng thái booking thành WAITING_CANCEL để admin có thể xử lý hủy
            $booking->status = BookingStatus::WAITING_CANCEL->value;
            $booking->reason_cancel = $reason;
            $booking->save();

            return ServiceReturn::success(
                message: __("booking.cancelled")
            );
        }catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
        catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi ServiceService@cancelBooking",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Xác nhận hủy booking, trường hợp admin xác nhận hủy booking
     * @param string $bookingId
     * @return ServiceReturn
     */
    public function confirmCancelBooking(string $bookingId): ServiceReturn
    {
        try {
            $booking = $this->bookingRepository->query()
                ->lockForUpdate()
                ->find($bookingId);
            if (!$booking) {
                throw new ServiceException(
                    message: __("booking.not_found")
                );
            };
            // Kiểm tra trạng thái booking có thể hủy không
            if ($booking->status != BookingStatus::WAITING_CANCEL->value) {
                throw new ServiceException(
                    message: __("booking.not_permission")
                );
            }

            // Cập nhật trạng thái booking thành CANCELED
            $booking->status = BookingStatus::CANCELED->value;
            $booking->save();
            // Gửi thông báo cho khách hàng
            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::BOOKING_CANCELLED,
                data: [
                    'booking_id' => $booking->id,
                    'reason' => $booking->reason_cancel,
                ]
            );
            // Gửi thông báo cho KTV
            SendNotificationJob::dispatch(
                userId: $booking->ktv_user_id,
                type: NotificationType::BOOKING_CANCELLED,
                data: [
                    'booking_id' => $booking->id,
                    'reason' => $booking->reason_cancel,
                ]
            );

            // Gửi job để hoàn lại tiền cho khách hàng
            RefundBookingCancelJob::dispatch($booking->id, $booking->reason_cancel);

            return ServiceReturn::success(
                message: __("booking.canceled")
            );
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
        catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi ServiceService@confirmCancelBooking",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }


    /**
     * Kiểm tra booking
     * @param string $bookingId
     * @return ServiceReturn
     */
    public function checkBooking(string $bookingId): ServiceReturn
    {
        try {
            $booking = $this->bookingRepository->query()->find($bookingId);
            if (!$booking) {
                return ServiceReturn::error(
                    message: __("booking.not_found")
                );
            }
            if ($booking->status == BookingStatus::PENDING->value) {
                return ServiceReturn::success(
                    data: [
                        'status' => 'waiting',
                    ]
                );
            } elseif ($booking->status == BookingStatus::CONFIRMED->value) {
                return ServiceReturn::success(
                    data: [
                        'status' => 'confirmed',
                        'data' => [
                            'booking_id' => $booking->id,
                            'service_name' => $booking->service->name,
                            'date' => Carbon::make($booking->booking_time)->format('d/m/Y H:i'),
                            'location' => $booking->address,
                            'technician' => $booking->ktvUser->name,
                            'total_price' => $booking->price,
                        ]
                    ]
                );
            } elseif (in_array($booking->status, [BookingStatus::CANCELED->value, BookingStatus::PAYMENT_FAILED->value])) {
                return ServiceReturn::success(
                    data: [
                        'status' => 'failed',
                    ]
                );
            } else {
                return ServiceReturn::error(
                    message: __("booking.not_found")
                );
            }
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi ServiceService@checkBooking",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Lấy chi tiết thông tin booking
     * @param int $bookingId
     * @return ServiceReturn
     */
    public function detailBooking(int $bookingId): ServiceReturn
    {
        try {
            $user = Auth::user();
            if ($user->role !== UserRole::CUSTOMER->value && $user->role !== UserRole::KTV->value) {
                return ServiceReturn::error(
                    message: __("common_error.unauthorized")
                );
            }
            $booking = $this->bookingRepository->query()->find($bookingId);
            if (!$booking) {
                return ServiceReturn::error(
                    message: __("booking.not_found")
                );
            }
            if ($user->role === UserRole::CUSTOMER->value && $booking->user_id !== $user->id) {
                return ServiceReturn::error(
                    message: __("common_error.unauthorized")
                );
            }
            if ($user->role === UserRole::KTV->value && $booking->ktv_user_id !== $user->id) {
                return ServiceReturn::error(
                    message: __("common_error.unauthorized")
                );
            }
            return ServiceReturn::success(
                data: $booking
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi ServiceService@detailBooking",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Bắt đầu thực hiện dịch vụ
     * @param string $booking_id
     * @return ServiceReturn
     */
    public function startBooking(string $booking_id): ServiceReturn
    {
        DB::beginTransaction();
        try {

            $user = Auth::user();
            // Lock Booking Row
            $booking = $this->bookingRepository->query()
                ->lockForUpdate()
                ->where('id', $booking_id)
                ->where('status', BookingStatus::CONFIRMED->value)
                ->where('start_time', null)
                ->where('end_time', null)
                ->first();

            if (!$booking) {
                throw new ServiceException(
                    message: __("booking.not_found")
                );
            }
            // Kiểm tra quyền thực hiện (KTV chỉ có thể bắt đầu dịch vụ của mình)
            if ($user->role != UserRole::KTV->value || $user->id != $booking->ktv_user_id) {
                throw new ServiceException(
                    message: __("common_error.unauthorized")
                );
            }
            $bookingTime = Carbon::make($booking->booking_time);
            // Không cho phép làm lịch của ngày mai hoặc ngày hôm qua
            if (!$bookingTime->isToday()) {
                throw new ServiceException(message: __("booking.only_start_today_bookings"));
            }

            // Gửi thông báo cho khách hàng biết rằng lịch đã bắt đầu
            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::BOOKING_START,
                data: [
                    'booking_id' => $booking->id,
                ]
            );
            $booking->status = BookingStatus::ONGOING->value;
            $booking->start_time = now();
            $booking->save();

            DB::commit();

            return ServiceReturn::success(
                data: [
                    'status' => BookingStatus::ONGOING->value,
                    'start_time' => $booking->start_time,
                    'duration' => $booking->duration,
                    'booking' => $booking,
                ]
            );
        }
        catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
        catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi ServiceService@startBooking",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }


    /**
     * Hoàn thành đơn hàng tiến hành thanh toán cho ktv và  tính phí hoa hồng cho các user khác
     * @param int $bookingId
     * @param bool $proactive
     * @return ServiceReturn
     */
    public function finishBooking(int $bookingId, bool $proactive = false)
    {
        try {
            // Lock Booking Row
            $booking = $this->bookingRepository->query()
                ->lockForUpdate()
                ->where('id', $bookingId)
                ->whereIn('status', [
                    BookingStatus::ONGOING->value,
                    BookingStatus::COMPLETED->value,
                ])
                ->first();
            if (!$booking) {
                throw new ServiceException(message: __("booking.not_found"));
            }

            // Kiểm tra trạng thái đơn hàng nếu hoàn thành rồi thì return luôn
            if ($booking->status === BookingStatus::COMPLETED->value) {
                return ServiceReturn::success(
                    data: [
                        'booking_id' => $booking->id,
                        'end_time' => $booking->end_time,
                        'already_finished' => true,
                    ],
                );
            }

            $now = now();
            $startTime = Carbon::make($booking->start_time);

            if ($proactive) {
                $userCurrent = Auth::user();
                if ($userCurrent->role == UserRole::KTV->value && $userCurrent->id != $booking->ktv_user_id) {
                    throw new ServiceException(
                        message: __("common_error.unauthorized")
                    );
                }
            }

            // Chỉ cho phép finish khi đã đến thời gian dự kiến hoặc đã qua
            // Cho phép finish 10 phút trước khi đến thời gian dự kiến
            if ($now->lessThan($startTime->copy()->addMinutes($booking->duration)->subMinutes(10))) {
                throw new ServiceException(
                    message: __("booking.not_permission_at_this_time")
                );
            }

            $booking->status = BookingStatus::COMPLETED->value;
            $booking->end_time = $now;
            $booking->save();

            // Thanh toán cho KTV và tính phí hoa hồng cho các user khác
            WalletTransactionBookingJob::dispatch(
                bookingId: $booking->id,
                case: WalletTransBookingCase::FINISH_BOOKING,
            );

            // gửi thông báo cho khách hàng biết rằng lịch đã hoàn thành
            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::BOOKING_COMPLETED,
                data: [
                    'booking_id' => $booking->id,
                ]
            );
            return ServiceReturn::success(
                data: [
                    'booking_id' => $booking->id,
                    'end_time' => $now,
                    'already_finished' => false,
                ],
            );
        }
        catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
        catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi BookingService@finishBooking",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }


    /**
     *  --------- Protected methods ---------
     */

    /**
     * Kiểm tra dịch vụ có phù hợp để book dịch vụ không
     * @param int $serviceId ID của dịch vụ cần kiểm tra
     * @return Service
     * @throws ServiceException
     */
    protected function checkServiceAllowBooking(int $serviceId)
    {
        $service = $this->serviceRepository->query()
            ->where('id', $serviceId)
            ->first();
        // Kiểm tra dịch vụ có tồn tại không
        if (!$service) {
            throw new ServiceException(
                message: __("booking.service.not_found")
            );
        }
        // Dịch vụ có đang hoạt động không
        if (!$service->is_active) {
            throw new ServiceException(
                message: __("booking.service.not_active")
            );
        }
        return $service;
    }

    /**
     * Kiểm tra option dịch vụ có tồn tại không và thuộc về dịch vụ này
     * @param int $categoryId ID của dịch vụ cần kiểm tra
     * @param int $optionId ID của option dịch vụ cần kiểm tra
     * @throws ServiceException
     * @return CategoryPrice
     */
    protected function checkOptionServiceAllowBooking(int $categoryId, int $optionId)
    {
        $serviceOption = $this->categoryPriceRepository
            ->query()
            ->where('category_id', $categoryId)
            ->where('id', $optionId)
            ->first();
        if (!$serviceOption) {
            throw new ServiceException(
                message: __("booking.service_option.not_match")
            );
        }
        return $serviceOption;
    }

    /**
     * Kiểm tra KTV có trống trong khoảng thời gian này không
     * @param int $ktvId
     * @param Carbon $startTime
     * @param int $duration
     * @throws ServiceException
     */
    protected function checkKtvAvailabilityToBooking(int $ktvId, Carbon $startTime, int $duration)
    {
        // Thời gian hiện tại
        $now = Carbon::now();
        // Lấy thông tin kỹ thuật viên
        $ktv = $this->userRepository->queryUser()
            ->where('id', $ktvId)
            ->where('role', UserRole::KTV->value)
            ->first();
        if (!$ktv) {
            throw new ServiceException(
                message: __("booking.ktv.not_found")
            );
        }

        // Lấy thời gian nghỉ giữa 2 lần phục vụ của kỹ thuật viên
        // Mục đích: Breaktime dc coi như là thời gian đi lại, nghỉ ngơi, ...
        $breakTime = (int) $this->configService->getConfigValue(ConfigName::BREAK_TIME_GAP);

        // Thời gian kết thúc đặt lịch dự kiến  = Thời gian bắt đầu + Thời gian dịch vụ + Thời gian nghỉ
        $endTimeWithBreak = $startTime->copy()->addMinutes($duration + $breakTime);

        // Thời gian book phải sau thời gian hiện tại ít nhất BreakTime để có thể lên lịch
        if ($startTime->lt($now->copy()->addMinutes($breakTime))) {
            throw new ServiceException(
                message: __("booking.book_time.book_time_not_valid", ['time' => $now->copy()->addMinutes($breakTime)->format('H:i')])
            );
        }

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
            if ($schedule->working_schedule){
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
            $isOverlapping = $startTime->lt($existingEndWithBreak) && $endTimeWithBreak->gt($existingStart);
            if ($isOverlapping) {
                // Duyệt tiếp các booking sau đó để tìm xem khi nào KTV thực sự rảnh liên tục đủ thời gian của dịch vụ mới
                // Ở đây ta đơn giản hóa: Gợi ý là ngay sau khi booking này kết thúc
                $suggestedTime = $existingEndWithBreak->copy()->format('H:i');
                throw new ServiceException(
                    message: __("booking.book_time.overlapping", ['time' => $suggestedTime])
                );
            }
        }
    }



    /**
     * Tính toán giá cuối cùng sau khi áp dụng mã giảm giá
     * @param int $serviceId
     * @param $price
     * @param int|null $couponId
     * @return array
     * @throws ServiceException
     */
    private function calculateFinalPrice(int $serviceId, $price, ?int $couponId): array
    {
        $discountAmount = 0.0;
        if ($couponId) {
            // Kiểm tra mã giảm giá có hợp lệ không
            $couponValidation = $this->couponService->validateUseCoupon(
                (string) $couponId,
                (string) $serviceId,
                $price
            );

            if (!$couponValidation->isError()) {
                $discountAmount = $couponValidation->getData()['discount_amount'];
            }else{
                throw new ServiceException(
                    message: $couponValidation->getMessage()
                );
            }
        }

        return [
            'price_before_discount' => $price,
            'final_price' => $price - $discountAmount,
            'discount_amount' => $discountAmount
        ];
    }

}
