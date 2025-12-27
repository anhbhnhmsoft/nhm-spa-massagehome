<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Jobs\SendNotificationJob;
use App\Repositories\BookingRepository;
use App\Repositories\CouponRepository;
use App\Repositories\ServiceRepository;
use App\Enums\ConfigName;
use App\Enums\PaymentType;
use App\Jobs\WalletTransactionBookingJob;
use App\Models\Config;
use App\Repositories\ServiceOptionRepository;
use App\Repositories\UserRepository;
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
        protected CouponService $couponService,
        protected WalletRepository $walletRepository,
        protected WalletTransactionRepository $walletTransactionRepository,
        protected UserRepository $userRepository,
        protected ConfigService $configService,
        protected WalletService $walletService,
    ) {
        parent::__construct();
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
     * @param int|null $couponId
     * @param string $address
     * @param string $latitude
     * @param string $longitude
     * @param string $bookTime
     * @param string|null $note
     * @return ServiceReturn
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
            $service = $this->serviceRepository->query()->find($serviceId);
            // Dịch vụ có đang hoạt động không
            if (!$service->is_active) {
                return ServiceReturn::error(
                    message: __("booking.service.not_active")
                );
            }
            // kiểm tra option dịch vụ
            $serviceOption = $this->serviceOptionRepository->query()->find($optionId);
            if ($serviceOption->service_id != $serviceId) {
                return ServiceReturn::error(
                    message: __("booking.service_option.not_match")
                );
            }

            $priceData = $this->calculateFinalPrice($serviceId, $serviceOption, $couponId);
            $finalPrice = $priceData['final_price'];
            $priceBeforeDiscount = $priceData['price_before_discount'];
            $discountAmount = $priceData['discount_amount'];

            // Kiểm tra wallet người dùng và wallet kỹ thuật viên trước
            $userWallet = $this->walletRepository->query()->where('user_id', $user->id)->first();
            $technicianWallet = $this->walletRepository->query()->where('user_id', $service->user_id)->first();

            if (!$userWallet || $userWallet->is_active == false) {
                return ServiceReturn::error(
                    message: __("booking.wallet.not_active")
                );
            }
            if (!$technicianWallet || $technicianWallet->is_active == false) {
                return ServiceReturn::error(
                    message: __("booking.wallet.tech_not_active")
                );
            }

            $balanceCustomer = $userWallet->balance;
            $balanceTechnician = $technicianWallet->balance;

            // kiểm tra số dư ví của khách hàng
            if ($balanceCustomer < $finalPrice) {
                return ServiceReturn::success(
                    data: [
                        'status' => false,
                        'failed' => [
                            'not_enough_money' => true,
                            'final_price' => $finalPrice,
                            'balance_customer' => $balanceCustomer
                        ]
                    ],
                    message: __("booking.wallet.not_enough")
                );
            }

            // lấy mức chiết khấu của nhà cung cấp
            /**
             * @var ServiceReturn $rateDiscount
             */
            $rateDiscount = $this->configService->getConfig(ConfigName::DISCOUNT_RATE);
            if ($rateDiscount->isError()) {
                return ServiceReturn::error(
                    message: __("booking.discount_rate.not_found")
                );
            } else {
                /**
                 * @var Config $configModel
                 */
                $configModel = $rateDiscount->getData();
                // config_value phải là số
                $rate = floatval($configModel['config_value']);

                // Chiết khấu nhà cung cấp chịu dựa trên GIÁ TRƯỚC KHI GIẢM (priceBeforeDiscount)
                $discountTechnician = ($priceBeforeDiscount * $rate) / 100;

                if ($balanceTechnician < $discountTechnician) {
                    SendNotificationJob::dispatch(
                        userId: $user->id,
                        type: NotificationType::TECHNICIAN_WALLET_NOT_ENOUGH,
                        data: [
                            'booking_time' => Carbon::parse($bookTime)->format('Y-m-d H:i:s'),
                            'price' => $finalPrice,
                        ]
                    );
                    // Nếu Kỹ thuật viên không đủ tiền chiết khấu (để hệ thống lấy lại)
                    return ServiceReturn::error(
                        message: __("booking.wallet.tech_not_enough")
                    );
                }
            }

            // Lấy thời gian nghỉ giữa 2 lần phục vụ của kỹ thuật viên
            /**
             * @var ServiceReturn $breakTimeGapReturn
             */
            $breakTimeGapReturn = $this->configService->getConfig(ConfigName::BREAK_TIME_GAP);
            if ($breakTimeGapReturn->isError()) {
                return ServiceReturn::error(
                    message: __("booking.break_time_gap.not_found")
                );
            }

            $breakTime = (int) $breakTimeGapReturn->getData()['config_value'];
            if (Carbon::parse($bookTime)->addMinutes($breakTime)->lt(Carbon::now())) {
                return ServiceReturn::error(
                    message: __("booking.book_time_not_valid")
                );
            }

            $currentBookingStartTime = Carbon::parse($bookTime);
            $isOverlapping = $this->checkKtvAvailability(
                $service->user_id,
                $currentBookingStartTime,
                $serviceOption->duration,
                $breakTime,
            );
            if ($isOverlapping) {
                return ServiceReturn::error(
                    message: __("booking.ktv_is_busy_at_this_time") // KTV đã có lịch trong khung giờ này
                );
            }
            $booking = $this->bookingRepository->create([
                'user_id' => $user->id,
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
                'ktv_user_id' => $service->user_id,
            ]);

            // Bắn notif cho người dùng khi đặt lịch thành công
            SendNotificationJob::dispatch(
                userId: $user->id,
                type: NotificationType::TECHNICIAN_WALLET_NOT_ENOUGH,
                data: [
                    'booking_id' => $booking->id,
                    'service_id' => $service->id,
                    'booking_time' => $currentBookingStartTime->format('Y-m-d H:i:s'),
                    'price' => $finalPrice,
                ]
            );

            DB::commit();

            // xử lý giao dịch, ghi lại lịch sử dùng coupon, tính phí affiliate, chiết khấu cho nhà cung cấp
            WalletTransactionBookingJob::dispatch($booking->id, $couponId, $user->id, $serviceId);

            // $this->walletService->paymentInitBooking($booking->id);
            // $this->couponService->useCouponAndSyncCache($couponId, $user->id, $serviceId, $booking->id);

            return ServiceReturn::success(
                data: [
                    'status' => true,
                    'success' => [
                        'booking_id' => $booking->id,
                    ]
                ]
            );
        } catch (ServiceException $exception) {
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
     * @param BookingStatus $status
     * @return ServiceReturn
     */
    public function cancelBooking(string $bookingId, BookingStatus $status): ServiceReturn
    {
        try {
            $booking = $this->bookingRepository->query()->find($bookingId);
            if (!$booking) {
                return ServiceReturn::error(
                    message: __("booking.not_found")
                );
            }
            $booking->status = $status->value;
            $booking->save();
            return ServiceReturn::success(
                message: __("booking.cancelled")
            );
        } catch (\Exception $exception) {
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
            if($user->role !== UserRole::CUSTOMER->value && $user->role !== UserRole::KTV->value ){
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
            if($user->role === UserRole::CUSTOMER->value && $booking->user_id !== $user->id){
                return ServiceReturn::error(
                    message: __("common_error.unauthorized")
                );
            }
            if($user->role === UserRole::KTV->value && $booking->ktv_user_id !== $user->id){
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
    public function startBooking(string $booking_id) : ServiceReturn
    {
        try {
            $user = Auth::user();
            $booking = $this->bookingRepository->query()->find($booking_id);
            if (!$booking) {
                return ServiceReturn::error(
                    message: __("booking.not_found")
                );
            }
            if($booking->start_time !== null || $booking->status !== BookingStatus::CONFIRMED->value){
                return ServiceReturn::error(
                    message: __("booking.already_started")
                );
            }
            if (
                $user->role != UserRole::KTV->value &&
                $user->id != $booking->ktv_user_id) {
                return ServiceReturn::error(
                    message: __("common_error.unauthorized")
                );
            }
            if (
                $booking->status != BookingStatus::CONFIRMED->value
                || $booking->start_time != null
                || $booking->end_time != null
                || $booking->duration == null
            ) {
                return ServiceReturn::error(
                    message: __("booking.status_not_confirmed")
                );
            }
            $booking->status = BookingStatus::ONGOING->value;
            $booking->start_time = now();
            $booking->save();
            return ServiceReturn::success(
                data: [
                    'status' => BookingStatus::ONGOING->value,
                    'start_time' => $booking->start_time,
                    'duration' => $booking->duration,
                    'booking' => $booking,
                ]
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi ServiceService@startBooking",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    //-------- private method --------

    private function checkKtvAvailability(int $ktvId, Carbon $startTime, int $duration, int $breakTimeGap): bool
    {
        $endTimeWithBreak = $startTime->copy()->addMinutes($duration + $breakTimeGap);

        $bookingsInDay = $this->bookingRepository->query()
            ->where('ktv_user_id', $ktvId)
            ->whereDate('booking_time', $startTime->toDateString())
            ->whereIn('status', [
                BookingStatus::CONFIRMED->value,
                BookingStatus::ONGOING->value,
                BookingStatus::PENDING->value
            ])
            ->get();

        foreach ($bookingsInDay as $existing) {
            // Thông tin booking cũ (Booking B)
            $bStart = Carbon::parse($existing->booking_time);
            $bDuration = (int) $existing->duration;

            // Thời điểm kết thúc của B bao gồm cả thời gian nghỉ
            // End B = Start B + Duration B + BreakTime
            $bEndWithBreak = $bStart->copy()->addMinutes($bDuration)->addMinutes($breakTimeGap);

            // Thời điểm kết thúc của A (Booking mới) bao gồm cả thời gian nghỉ
            // End A = Start A + Duration A + BreakTime
            $aEndWithBreak = $startTime->copy()->addMinutes($duration)->addMinutes($breakTimeGap);

            /**
             * LOGIC KIỂM TRA TRÙNG:
             * Hai khoảng thời gian trùng nhau khi: (StartA < EndB) VÀ (EndA > StartB)
             * Ở đây End được tính kèm cả BreakTime để đảm bảo khoảng nghỉ.
             */
            $isOverlapping = $startTime->lt($bEndWithBreak) && $endTimeWithBreak->gt($bStart);

            if ($isOverlapping) {
                return true;
            }
        }

        return false; // Trống lịch
    }

    private function calculateFinalPrice(int $serviceId, $serviceOption, ?int $couponId): array
    {
        $priceBeforeDiscount = $serviceOption->price;
        $discountAmount = 0.0;

        if ($couponId) {
            $couponValidation = $this->couponService->validateUseCoupon(
                (string) $couponId,
                (string) $serviceId,
                $priceBeforeDiscount
            );

            if (!$couponValidation->isError()) {
                $discountAmount = $couponValidation->getData()['discount_amount'];
            }
        }

        return [
            'price_before_discount' => $priceBeforeDiscount,
            'final_price' => $priceBeforeDiscount - $discountAmount,
            'discount_amount' => $discountAmount
        ];
    }
}
