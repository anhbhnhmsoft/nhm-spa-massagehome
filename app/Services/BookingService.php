<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\NotificationType;
use App\Enums\ServiceDuration;
use App\Jobs\SendNotificationJob;
use App\Models\Coupon;
use App\Repositories\BookingRepository;
use App\Repositories\CouponRepository;
use App\Repositories\ServiceRepository;
use App\Enums\ConfigName;
use App\Enums\PaymentType;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\WalletTransactionBookingJob;
use App\Models\Config;
use App\Services\ConfigService;
use App\Repositories\ServiceOptionRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Services\WalletService;
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
                message: "Lỗi ServiceService@categoryPaginate",
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

            $priceBeforeDiscount = $serviceOption->price;
            $discountAmount = 0.0;
            $finalPrice  = $priceBeforeDiscount;

            if ($couponId) {
                $couponValidation = $this->couponService->validateUseCoupon(
                    couponId: (string) $couponId,
                    serviceId: (string) $serviceId,
                    priceBeforeDiscount: $priceBeforeDiscount
                );

                if ($couponValidation->isError()) {
                    return ServiceReturn::error(
                        message: $couponValidation->getMessage()
                    );
                }

                $validationData = $couponValidation->getData();
                $discountAmount = $validationData['discount_amount'];
                $finalPrice = $priceBeforeDiscount - $discountAmount;
            }


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
                        type: NotificationType::BOOKING_CONFIRMED,
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
            // Giả định config_value là số phút
            $breakTimeGap = 0;
            /**
             * @var ServiceReturn $breakTimeGapReturn
             */
            $breakTimeGapReturn = $this->configService->getConfig(ConfigName::BREAK_TIME_GAP);
            if ($breakTimeGapReturn->isError()) {
                return ServiceReturn::error(
                    message: __("booking.break_time_gap.not_found")
                );
            }

            // Lấy giá trị break time (phút)
            $breakTimeGapMinutes = (int) $breakTimeGapReturn->getData();

            // 1. Chuẩn bị dữ liệu thời gian
            $durationMinutes = (int) $serviceOption->duration;
            $currentBookingStartTime = Carbon::parse($bookTime); // Start A

            // Thời gian kết thúc dự kiến của booking đang đặt (End A = Start A + Duration + Break)
            $currentBookingEndTime = $currentBookingStartTime->copy()
                ->addMinutes($durationMinutes)
                ->addMinutes($breakTimeGapMinutes);

            // 2. Định nghĩa khung thời gian tìm kiếm (±3 tiếng)
            $searchWindowStart = $currentBookingStartTime->copy()->subHours(3);
            $searchWindowEnd = $currentBookingStartTime->copy()->addHours(3);

            // 3. Truy vấn kiểm tra trùng lặp
            $hasOverlappingBooking = $this->bookingRepository->query()
                ->where('ktv_user_id', $service->user_id)
                ->whereDate('booking_time', $currentBookingStartTime->toDateString())
                ->whereBetween('booking_time', [$searchWindowStart, $searchWindowEnd])
                ->whereNotIn('status', [BookingStatus::CANCELED->value])
                ->where(function ($q) use ($currentBookingStartTime, $currentBookingEndTime, $breakTimeGapMinutes) {
                    /**
                     * booking_time + (duration * INTERVAL '1 minute') + (? * INTERVAL '1 minute')
                     */
                    $q->where(function ($inner) use ($currentBookingStartTime, $breakTimeGapMinutes) {
                        // StartA < EndB (Thời gian bắt đầu mới < Thời gian kết thúc dự kiến cũ)
                        $inner->whereRaw(
                            "booking_time + (duration + ?) * INTERVAL '1 minute' > ?",
                            [$breakTimeGapMinutes, $currentBookingStartTime]
                        );
                    })
                    ->where('booking_time', '<', $currentBookingEndTime); // EndA > StartB
                })
                ->first();

            if ($hasOverlappingBooking) {
                return ServiceReturn::error(
                    message: __("booking.time_slot_not_available")
                );
            }
            // khởi tạo booking mới
            $booking =  $this->bookingRepository->create([
                'user_id' => $user->id,
                'service_id' => $serviceId,
                'coupon_id' => $couponId,
                'duration' => $durationMinutes,
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
                'service_option_id' => $optionId,
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
            }elseif ($booking->status == BookingStatus::CONFIRMED->value) {
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
            }else{
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
     * @param string $bookingId
     * @return ServiceReturn
     */
    public function detailBooking(string $bookingId): ServiceReturn
    {
        try {
            $booking = $this->bookingRepository->query()->find($bookingId);
            if (!$booking) {
                return ServiceReturn::error(
                    message: __("booking.not_found")
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
}
