<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Models\Coupon;
use App\Repositories\BookingRepository;
use App\Repositories\CouponRepository;
use App\Repositories\ServiceRepository;
use App\Enums\ConfigName;
use App\Enums\PaymentType;
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
        ?int            $couponId = null,
        string          $address,
        string          $latitude,
        string          $longitude,
        string          $bookTime,
        ?string         $note = null,
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
                $couponValidation = $this->couponService->validateCouponWithCache(
                    couponId: (string) $couponId,
                    userId: (string) $user->id,
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
                        'not_enough_money' => true,
                        'final_price' => $finalPrice,
                        'balance_customer' => $balanceCustomer
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
                    // Nếu Kỹ thuật viên không đủ tiền chiết khấu (để hệ thống lấy lại)
                    return ServiceReturn::error(
                        message: __("booking.wallet.tech_not_enough")
                    );

                    /**
                     * Cần bổ sung logic gửi thông báo nạp ví cho kỹ thuật viên
                     */
                }
            }
            // ...
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

            // Kiểm tra logic thời gian đặt có hợp lệ không
            $durationMinutes = $serviceOption->duration; // duration tính bằng phút
            $currentBookingStartTime = Carbon::parse($bookTime); // Thời gian đặt lịch (Start A)

            // Tính thời gian kết thúc TOÀN BỘ (Dịch vụ + Thời gian nghỉ) cho booking mới (End A)
            $currentBookingEndTime = $currentBookingStartTime
                ->copy()
                ->addMinutes($durationMinutes)
                ->addMinutes($breakTimeGapMinutes);

            // --- Kiểm tra trùng lặp theo Kỹ thuật viên (Technician) ---
            $technicianId = $service->user_id;

            $hasOverlappingBooking = $this->bookingRepository->query()
                // booking_time của booking cũ (Start B) + duration (phút) + breakTimeGap (phút) > currentBookingStartTime (Start A)
                ->whereHas('service', function ($q) use ($technicianId) {
                    $q->where('user_id', $technicianId);
                })
                // Start Mới (<) End Cũ
                ->where('end_time', '>', $currentBookingStartTime)

                // End Mới (>) Start Cũ
                ->where('start_time', '<', $currentBookingEndTime)
                ->exists();

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
                'start_time' => $currentBookingStartTime,
                'end_time' => $currentBookingEndTime,
                'status' => BookingStatus::CONFIRMED->value,
                'price' => $finalPrice,
                'price_before_discount' => $priceBeforeDiscount,
                'payment_type' => PaymentType::BY_POINTS->value,
                'note' => $note ?? '',
                'address' => $address ?? '',
                'latitude' => $latitude ?? 0,
                'longitude' => $longitude ?? 0,
                'service_option_id' => $optionId,
            ]);

            DB::commit();

            // cần bổ sung action thông báo từ số dư của khách hàng 
            // xử lý giao dịch, ghi lại lịch sử dùng coupon, tính phí affiliate, chiết khấu cho nhà cung cấp
            WalletTransactionBookingJob::dispatch($booking->id, $couponId, $user->id, $serviceId)->onQueue('transactions-payment');

            // $this->walletService->paymentInitBooking($booking->id);
            // $this->couponService->useCouponAndSyncCache($couponId, $user->id, $serviceId, $booking->id);

            return ServiceReturn::success(
                data: [
                    'final_price' => $finalPrice,
                    'discount_amount' => $discountAmount,
                    'booking_id' => $booking->id
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
}
