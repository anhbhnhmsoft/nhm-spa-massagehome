<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Controller\FilterDTO;
use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\PayCommissionFeeJob;
use App\Jobs\RefundBookingCancelJob;
use App\Jobs\SendNotificationJob;
use App\Models\User;
use App\Repositories\BookingRepository;
use App\Repositories\CouponRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\ServiceRepository;
use App\Enums\ConfigName;
use App\Enums\PaymentType;
use App\Jobs\WalletTransactionBookingJob;
use App\Models\Config;
use App\Repositories\ServiceOptionRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use Exception;
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
        protected ReviewRepository $reviewRepository,
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
            $userWallet = $this->walletRepository->query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();
            // lấy ví của ktv
            $technicianWallet = $this->walletRepository->query()
                ->where('user_id', $service->user_id)
                ->lockForUpdate()
                ->first();

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
                $discountTechnician = ($priceBeforeDiscount * (100 - $rate)) / 100;

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
                'status' => BookingStatus::CONFIRMED->value,
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
                type: NotificationType::BOOKING_SUCCESS,
                data: [
                    'booking_id' => $booking->id,
                    'service_id' => $service->id,
                    'booking_time' => $currentBookingStartTime->format('Y-m-d H:i:s'),
                    'price' => $finalPrice,
                ]
            );
            // Bắn notif cho KTV khi có lịch hẹn mới
            SendNotificationJob::dispatch(
                userId: $service->user_id, // KTV
                type: NotificationType::NEW_BOOKING_REQUEST,
                data: [
                    'booking_id' => $booking->id,
                    'customer_name' => $user->name,
                    'booking_time' => $currentBookingStartTime->format('Y-m-d H:i:s'),
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
     * @param string|null $reason
     * @param bool $proactive
     * @return ServiceReturn
     */
    public function cancelBooking(string $bookingId, BookingStatus $status, ?string $reason = null, $proactive = true): ServiceReturn
    {
        DB::beginTransaction();
        try {
            $userCurrent = Auth::user();
            // Lock Booking Row
            $booking = $this->bookingRepository->query()
                ->lockForUpdate()
                ->find($bookingId);
            if (!$booking) {
                return ServiceReturn::error(
                    message: __("booking.not_found")
                );
            };
            if ($proactive) {
                if ($userCurrent->id != $booking->user_id && $userCurrent->id != $booking->ktv_user_id && $userCurrent->role != UserRole::ADMIN->value) {
                    return ServiceReturn::error(
                        message: __("booking.not_permission")
                    );
                }
            }
            if ($booking->status != BookingStatus::PENDING->value && $booking->status != BookingStatus::CONFIRMED->value) {
                return ServiceReturn::error(
                    message: __("booking.not_permission")
                );
            }
            $booking->status = $status->value;
            $booking->reason_cancel = $reason;
            $booking->save();

            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::BOOKING_CANCELLED,
                data: [
                    'booking_id' => $booking->id,
                    'reason' => $reason,
                ]
            );
            SendNotificationJob::dispatch(
                userId: $booking->ktv_user_id,
                type: NotificationType::BOOKING_CANCELLED,
                data: [
                    'booking_id' => $booking->id,
                    'reason' => $reason,
                ]
            );

            RefundBookingCancelJob::dispatch($booking->id, $reason);
            DB::commit();
            return ServiceReturn::success(
                message: __("booking.cancelled")
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi ServiceService@cancelBooking",
                ex: $exception
            );
            throw $exception;
        }
    }

    /*
     * Hủy booking, hoàn lại tiền cho khách hàng
     * @param string $bookingId
     * @param string|null $reason
     * @return ServiceReturn
     */
    public function refundCancelBooking(string $bookingId, ?string $reason = null): ServiceReturn
    {
        DB::beginTransaction();
        try {

            $booking = $this->bookingRepository->query()->find($bookingId);
            if (!$booking) {
                return ServiceReturn::error(
                    message: __("booking.not_found")
                );
            }
            if ($booking->status != BookingStatus::CANCELED->value) {
                return ServiceReturn::error(
                    message: __("booking.not_canceled")
                );
            }

            // Lấy transaction gốc để tham khảo (chỉ đọc, không sửa)
            $transactionOfCustomer = $this->walletTransactionRepository->query()
                ->where("foreign_key", $booking->id)
                ->where('type', WalletTransactionType::PAYMENT->value)
                ->first();
            // Nếu chưa có transaction PAYMENT (cancel ngay sau khi tạo booking)
            // thì không cần refund, chỉ cần return success
            if (!$transactionOfCustomer) {
                LogHelper::debug("Booking #{$booking->id} chưa có transaction PAYMENT, không cần refund");
                return ServiceReturn::success(
                    data: [
                        'success' => true,
                        'message' => 'Booking chưa thanh toán, không cần hoàn tiền',
                    ],
                    message: __("booking.booking_refunded")
                );
            }

            // Kiểm tra đã refund chưa
            $existingRefund = $this->walletTransactionRepository->query()
                ->where('foreign_key', $booking->id)
                ->where('type', WalletTransactionType::REFUND->value)
                ->exists();

            if ($existingRefund) {
                return ServiceReturn::error(
                    message: __("booking.refunded")
                );
            }

            // Lấy wallet customer với lock
            $walletCustomer = $this->walletRepository->query()
                ->where('user_id', $booking->user_id)
                ->lockForUpdate()
                ->first();

            if (!$walletCustomer) {
                LogHelper::error("Lỗi không tìm thấy wallet customer #{$booking->user_id}");
                return ServiceReturn::error(
                    message: __("error.not_found_wallet")
                );
            }
            $currencyExchangeRate = $this->configService->getConfig(ConfigName::CURRENCY_EXCHANGE_RATE);
            if ($currencyExchangeRate->isError()) {
                LogHelper::error("Lỗi không tìm thấy config wallet exchange rate");
                return ServiceReturn::error(
                    message: __("error.config_wallet_error")
                );
            }

            $exchangeRate = $currencyExchangeRate->getData()['config_value'];

            // Tạo transaction REFUND mới
            $refundTransaction = $this->walletTransactionRepository->create([
                'wallet_id' => $walletCustomer->id,
                'type' => WalletTransactionType::REFUND->value,
                'point_amount' => $booking->price,
                'amount' => $booking->price,
                'balance_after' => $walletCustomer->balance + $booking->price,
                'money_amount' => $booking->price,
                'exchange_rate_point' => $exchangeRate,
                'status' => WalletTransactionStatus::COMPLETED->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                'foreign_key' => $booking->id,
                'description' => "Hoàn tiền booking #{$booking->id}",
                'expired_at' => now(),
                'transaction_id' => null,
            ]);

            // Cập nhật balance
            $walletCustomer->balance += $booking->price;
            $walletCustomer->save();

            $ktv = $booking->ktvUser;

            // Gửi thông báo
            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::BOOKING_REFUNDED,
                data: [
                    'booking_id' => $booking->id,
                    'amount' => $booking->price,
                    'reason' => $reason,
                ]
            );
            DB::commit();
            return ServiceReturn::success(
                message: __("booking.refunded")
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi ServiceService@refundCancelBooking",
                ex: $exception
            );
            throw $exception;
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
                ->find($booking_id);
            if (!$booking) {
                return ServiceReturn::error(
                    message: __("booking.not_found")
                );
            }

            if (Carbon::parse($booking->booking_time) > Carbon::now()) {
                return ServiceReturn::error(
                    message: __("booking.booking_time_not_yet")
                );
            }

            if ($booking->start_time !== null || $booking->status !== BookingStatus::CONFIRMED->value) {
                return ServiceReturn::error(
                    message: __("booking.already_started")
                );
            }
            if (
                $user->role != UserRole::KTV->value ||
                $user->id != $booking->ktv_user_id
            ) {
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
        } catch (\Exception $exception) {
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
        DB::beginTransaction();
        try {
            $userCurrent = Auth::user();
            // Lock Booking Row
            $booking = $this->bookingRepository->query()
                ->lockForUpdate()
                ->find($bookingId);
            if (!$booking) {
                if ($proactive) {
                    return ServiceReturn::error(
                        message: __("error.not_found_booking")
                    );
                } else {
                    throw new \Exception(__("error.not_found_booking"));
                }
            }
            if ($booking->status != BookingStatus::ONGOING->value) {
                if ($proactive) {
                    return ServiceReturn::error(
                        message: __("booking.status_not_ongoing")
                    );
                } else {
                    throw new \Exception(__("booking.status_not_ongoing"));
                }
            }
            // Kiểm tra quyền (chỉ khi có user - không áp dụng cho cronjob auto-finish)
            if ($userCurrent && $userCurrent->id != $booking->ktv_user_id && $userCurrent->id != $booking->user_id) {
                if ($proactive) {
                    return ServiceReturn::error(
                        message: __("common_error.unauthorized")
                    );
                } else {
                    throw new \Exception(__("common_error.unauthorized"));
                }
            }
            // Kiểm tra thời gian (chỉ khi user finish thủ công)
            // Cronjob auto-finish không cần kiểm tra vì đã quá hạn rồi
            if ($proactive) {
                $expectedEndTime = Carbon::parse($booking->start_time)->addMinutes($booking->duration);
                $now = now();
                // Chỉ cho phép finish khi đã đến thời gian dự kiến hoặc đã qua
                if ($now->lessThan($expectedEndTime)) {
                    return ServiceReturn::error(
                        message: __("booking.not_permission_at_this_time")
                    );
                }
            }
            $booking->status = BookingStatus::COMPLETED->value;
            $booking->end_time = now();
            $booking->save();
            // lấy ví của ktv
            $wallet = $this->walletRepository->query()
                ->where('user_id', $booking->ktv_user_id)
                ->lockForUpdate()
                ->first();
            if (!$wallet) {
                if ($proactive) {
                    return ServiceReturn::error(
                        message: __("error.not_found_wallet")
                    );
                } else {
                    throw new \Exception(__("error.not_found_wallet"));
                }
            }
            // lấy giao dịch được khởi tạo cho ktv khi khách hàng đặt lịch
            $walletTransaction = $this->walletTransactionRepository->query()->where('wallet_id', $wallet->id)->where('foreign_key', $booking->id)->first();
            if (!$walletTransaction) {
                if ($proactive) {
                    return ServiceReturn::error(
                        message: __("error.not_found_wallet_transaction")
                    );
                } else {
                    throw new \Exception(__("error.not_found_wallet_transaction"));
                }
            }
            $walletTransaction->status = WalletTransactionStatus::COMPLETED->value;
            $walletTransaction->save();
            // tính toán số tiền và trả cho ktv
            $wallet->balance = $walletTransaction->balance_after;
            $wallet->save();
            // tính toán phí hoa hồng và gửi tới các user khác
            PayCommissionFeeJob::dispatch($bookingId);
            // gửi thông báo cho ktv và khách hàng
            SendNotificationJob::dispatch(
                userId: $booking->ktv_user_id,
                type: NotificationType::BOOKING_COMPLETED,
                data: [
                    'booking_id' => $booking->id,
                ]
            );

            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::BOOKING_COMPLETED,
                data: [
                    'booking_id' => $booking->id,
                ]
            );
            DB::commit();
            return ServiceReturn::success(
                message: __("booking.completed")
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi BookingService@finishBooking",
                ex: $exception
            );
            if ($proactive) {
                return ServiceReturn::error(
                    message: $exception->getMessage()
                );
            }
            throw $exception;
        }
    }

    /**
     * Tính toán phí hoa hồng và gửi tới các user khác
     * @param int $bookingId
     * @return Exception
     */
    public function payCommissionFee(int $bookingId): void
    {
        DB::beginTransaction();
        try {
            $booking = $this->bookingRepository->find($bookingId);
            if (!$booking) {
                throw new \Exception(__("error.not_found_booking"));
            }
            if ($booking->status != BookingStatus::COMPLETED->value) {
                throw new \Exception(__("booking.status_not_completed"));
            }

            $resConfig = $this->configService->getConfig(ConfigName::DISCOUNT_RATE);
            if ($resConfig->isError()) {
                throw new ServiceException(
                    message: __("error.config_wallet_error")
                );
            }
            $discountRate = $resConfig->getData()['config_value'];
            /**
             * @var User $customer
             * @var User $staff
             */
            $customer = $booking->user;
            $staff = $booking->ktvUser;

            // chiết khấu thực tế = (giá trị gốc dịch vụ * %chiết khấu cho nhà cung cấp  )/ 100
            $commissionFee = ($booking->price_before_discount * (100 - $discountRate)) / 100;

            // Load configs
            $configs = [
                UserRole::AGENCY->value => $this->configService->getConfigAffiliate(UserRole::AGENCY),
                UserRole::KTV->value    => $this->configService->getConfigAffiliate(UserRole::KTV),
                UserRole::CUSTOMER->value => $this->configService->getConfigAffiliate(UserRole::CUSTOMER),
            ];

            foreach ($configs as $config) {
                if ($config->isError()) {
                    throw new ServiceException(__("error.config_wallet_error"));
                }
            }

            // Process Customer Referrer
            if ($customer->referrer) {
                $this->processReferralCommission($customer->referrer, $commissionFee, $bookingId, $configs);
            }

            // Process Staff Referrer
            if ($staff->referrer) {
                $this->processReferralCommission($staff->referrer, $commissionFee, $bookingId, $configs);
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi BookingService@payCommissionFee",
                ex: $exception
            );
            throw $exception;
        }
    }


    //-------- private method --------

    protected function processReferralCommission(User $referrer, float $commissionFee, int $bookingId, array $configs): void
    {
        $role = $referrer->role;
        $config = $configs[$role] ?? null;

        if (!$config) {
            return;
        }

        $data = $config->getData();
        $amount = $this->calculateCommissionFee(
            $commissionFee,
            $data['commission_rate'],
            $data['max_commission'],
            $data['min_commission']
        );
        $this->walletService->paymentCommissionFeeForRefferal($amount, $referrer->id, $bookingId);
    }

    protected function calculateCommissionFee(float $amountSystemReceive, float $commissionPercent, $maxCommission, $minCommission): int
    {
        $amount = $amountSystemReceive * (100 - $commissionPercent) / 100;
        if ($amount > $maxCommission) {
            return $maxCommission;
        }
        if ($amount < $minCommission) {
            return $minCommission;
        }
        return round($amount, 3);
    }

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


    /****************************** public method ******************************/

    /**
     * Lấy tổng thu nhập trong khoảng thời gian
     * @param User $user
     * @param string type
     * @return ServiceReturn
     */
    public function totalIncome(User $user, string $type = 'day'): ServiceReturn
    {
        try {
            $uniqueKey = 'user_' . $user->id;
            $cachedData = Caching::getCache(CacheKey::CACHE_KEY_TOTAL_INCOME, $uniqueKey);

            // Kiểm tra cache theo type
            if ($cachedData && $cachedData['type'] === $type) {
                return ServiceReturn::success(data: $cachedData['content']);
            }

            $wallet = $user->wallet;
            if (!$wallet) return ServiceReturn::error(__("error.wallet_not_found"));

            $now = Carbon::now();
            $fromDate = match ($type) {
                'day' => $now->copy()->startOfDay(),
                'week' => $now->copy()->subDays(7)->startOfDay(),
                'month' => $now->copy()->startOfMonth(),
                'quarter' => $now->copy()->subMonths(3)->startOfDay(),
                'year' => $now->copy()->startOfYear(),
                default => $now->copy()->startOfDay(),
            };
            $toDate = $now->copy()->endOfDay();

            $chartGroupBy = match ($type) {
                'day' => "to_char(created_at, 'HH24:00')", // Theo giờ
                'week', 'year', 'month' => "to_char(created_at, 'YYYY-MM-DD')", // Theo ngày
                default => "to_char(created_at, 'YYYY-MM-DD')",
            };

            // 1. Tổng thu nhập (Kỳ này)
            $totalIncome = $this->walletTransactionRepository->query()
                ->where('wallet_id', $wallet->id)
                ->where('type', WalletTransactionType::PAYMENT_FOR_KTV->value)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->sum('point_amount');

            // 2. Doanh thu thực nhận (Status: COMPLETED)
            $receivedIncome = $this->walletTransactionRepository->query()
                ->where('wallet_id', $wallet->id)
                ->where('type', WalletTransactionType::PAYMENT_FOR_KTV->value)
                ->where('status', WalletTransactionStatus::COMPLETED->value)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->sum('point_amount');

            // 3. Số khách
            $totalCustomers = $this->bookingRepository->query()
                ->where('ktv_user_id', $user->id)
                ->where('status', BookingStatus::COMPLETED->value)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->count();

            // 4. Thu nhập Affiliate
            $affiliateIncome = $this->walletTransactionRepository->query()
                ->where('wallet_id', $wallet->id)
                ->where('type', WalletTransactionType::AFFILIATE->value)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->sum('point_amount');

            // 5. Lượt review
            $totalReviews = $this->reviewRepository->query()
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->count();

            // 6. Dữ liệu biểu đồ
            if ($type === 'day') {
                $chartData = $this->walletTransactionRepository->query()
                    ->selectRaw("
            CASE
                WHEN CAST(to_char(created_at, 'HH24') AS INTEGER) < 6 THEN '00:00-06:00'
                WHEN CAST(to_char(created_at, 'HH24') AS INTEGER) < 12 THEN '06:00-12:00'
                WHEN CAST(to_char(created_at, 'HH24') AS INTEGER) < 18 THEN '12:00-18:00'
                ELSE '18:00-00:00'
            END as date,
            SUM(point_amount) as total
        ")
                    ->where('wallet_id', $wallet->id)
                    ->where('type', WalletTransactionType::PAYMENT_FOR_KTV->value)
                    ->whereBetween('created_at', [$fromDate, $toDate])
                    ->groupByRaw("date")
                    ->orderByRaw("MIN(created_at) ASC")
                    ->get();
            } else {
                // Điều chỉnh logic format ngày cho year, month, week
                $format = match ($type) {
                    'year'  => "to_char(created_at, 'YYYY-MM')", // Lấy theo tháng nếu là Year
                    'month', 'week' => "to_char(created_at, 'YYYY-MM-DD')", // Lấy theo ngày nếu là Month/Week
                    default => "to_char(created_at, 'YYYY-MM-DD')",
                };

                $chartData = $this->walletTransactionRepository->query()
                    ->selectRaw("$format as date, SUM(point_amount) as total")
                    ->where('wallet_id', $wallet->id)
                    ->where('type', WalletTransactionType::PAYMENT_FOR_KTV->value)
                    ->whereBetween('created_at', [$fromDate, $toDate])
                    ->groupByRaw("date")
                    ->orderBy('date', 'asc')
                    ->get();
            }

            $resultData = [
                'total_income' => (float)$totalIncome,
                'received_income' => (float)$receivedIncome,
                'total_customers' => $totalCustomers,
                'affiliate_income' => (float)$affiliateIncome,
                'total_reviews' => $totalReviews,
                'chart_data' => $chartData,
                'type_label' => $type
            ];

            Caching::setCache(
                key: CacheKey::CACHE_KEY_TOTAL_INCOME,
                value: ['type' => $type, 'content' => $resultData],
                uniqueKey: $uniqueKey,
                expire: 60 * 5,
            );
            return ServiceReturn::success(data: $resultData);

        } catch (\Exception $exception) {
            LogHelper::error("Lỗi BookingService@totalIncome", $exception);
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Lấy danh sách booking đang diễn ra (ONGOING)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOngoingBookings()
    {
        return $this->bookingRepository->query()
            ->where('status', BookingStatus::ONGOING->value)
            ->get();
    }

    /**
     * Kiểm tra và xử lý booking quá hạn (Auto Finish hoặc Warning)
     * @param \App\Models\ServiceBooking $booking
     * @return void
     */
    public function processOvertimeBooking($booking): void
    {
        try {
            $startTime = Carbon::parse($booking->start_time);
            $expectedEndTime = $startTime->copy()->addMinutes($booking->duration);
            $now = Carbon::now();

            // Tính thời gian quá hạn (âm = quá hạn)
            $overtimeMinutes = $now->diffInMinutes($expectedEndTime, false);

            // Nếu quá hạn hơn 10 phút → Tự động finish
            if ($overtimeMinutes <= -10) {
                LogHelper::debug("Auto-finishing booking {$booking->id} - Overtime: " . abs($overtimeMinutes) . " minutes");

                // Gửi thông báo cho KTV
                SendNotificationJob::dispatch(
                    userId: $booking->ktv_user_id,
                    type: NotificationType::BOOKING_AUTO_FINISHED,
                    data: [
                        'booking_id' => $booking->id,
                        'overtime_minutes' => abs($overtimeMinutes),
                    ]
                );

                // Gửi thông báo cho customer
                SendNotificationJob::dispatch(
                    userId: $booking->user_id,
                    type: NotificationType::BOOKING_AUTO_FINISHED,
                    data: [
                        'booking_id' => $booking->id,
                        'overtime_minutes' => abs($overtimeMinutes),
                    ]
                );

                // Auto finish booking
                $result = $this->finishBooking($booking->id, false);

                if ($result->isError()) {
                    LogHelper::debug("Failed to auto-finish booking {$booking->id}: " . $result->getMessage());
                } else {
                    LogHelper::debug("Successfully auto-finished booking {$booking->id}");
                }
            }
            // Nếu quá hạn 5-10 phút → Gửi cảnh báo (chỉ 1 lần)
            elseif ($overtimeMinutes <= -5 && $overtimeMinutes > -10) {
                // Kiểm tra đã gửi cảnh báo chưa
                if (!$booking->overtime_warning_sent) {
                    LogHelper::debug("Sending overtime warning for booking {$booking->id} - Overtime: " . abs($overtimeMinutes) . " minutes");

                    SendNotificationJob::dispatch(
                        userId: $booking->ktv_user_id,
                        type: NotificationType::BOOKING_OVERTIME_WARNING,
                        data: [
                            'booking_id' => $booking->id,
                            'overtime_minutes' => abs($overtimeMinutes),
                        ]
                    );

                    // Đánh dấu đã gửi cảnh báo
                    $booking->overtime_warning_sent = true;
                    $booking->save();
                }
            }
        } catch (\Exception $e) {
            LogHelper::error("Error processing overtime booking {$booking->id}: " . $e->getMessage(), $e);
        }
    }
}
