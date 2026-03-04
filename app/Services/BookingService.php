<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\Helper\CalculatePrice;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\Jobs\WalletTransCase;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Jobs\SendNotificationJob;
use App\Models\Category;
use App\Models\UserAddress;
use App\Models\Wallet;
use App\Repositories\BookingRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CouponRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\ServiceRepository;
use App\Enums\ConfigName;
use App\Enums\PaymentType;
use App\Jobs\WalletTransactionBookingJob;
use App\Models\ServiceBooking;
use App\Repositories\ServiceOptionRepository;
use App\Repositories\UserAddressRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserReviewApplicationRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Services\Validator\BookingValidator;
use App\Services\Validator\CouponValidator;
use App\Services\Validator\WalletValidator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingService extends BaseService
{
    public function __construct(
        protected BookingRepository               $bookingRepository,
        protected ServiceRepository               $serviceRepository,
        protected CouponRepository                $couponRepository,
        protected ServiceOptionRepository         $serviceOptionRepository,
        protected CategoryRepository              $categoryRepository,
        protected CouponService                   $couponService,
        protected WalletRepository                $walletRepository,
        protected WalletTransactionRepository     $walletTransactionRepository,
        protected UserReviewApplicationRepository $reviewApplicationRepository,
        protected UserRepository                  $userRepository,
        protected ConfigService                   $configService,
        protected WalletService                   $walletService,
        protected ReviewRepository                $reviewRepository,
        protected UserAddressRepository           $addressRepository,
        protected BookingValidator                $bookingValidator,
        protected CouponValidator                 $couponValidator,
        protected WalletValidator                 $walletValidator,
    )
    {
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
     * Check data và đưa ra giá tiền trước khi booking dịch vụ
     * @param array $data - Dựa theo Request PrepareBookingRequest
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function prepareBooking(array $data): ServiceReturn
    {
        return $this->execute(function () use ($data) {
            $userId = Auth::id();

            $dataRes = $this->validateBooking($data, $userId);

            $dataBookingToday = $this->bookingRepository->getBookingCustomerOnGoingInDay($userId);

            // Chỉ cần trả ra data này
            return [
                'break_time' => $dataRes['bookTimeData']['break_time'],
                'price' => $dataRes['priceData']['price'],
                'price_per_km' => $dataRes['priceData']['price_per_km'],
                'price_distance' => $dataRes['priceData']['price_distance'],
                'discount_coupon' => $dataRes['priceData']['discount_coupon'],
                'final_price' => $dataRes['priceData']['final_price'],
                'distance' => $dataRes['priceData']['distance'],
                'booking_today' => $dataBookingToday ? [
                    'id' => $dataBookingToday->id,
                    'status' => $dataBookingToday->status,
                    'booking_time' => $dataBookingToday->booking_time,
                    'start_time' => $dataBookingToday->start_time,
                ] : null,
            ];
        });
    }


    /**
     * Đặt lịch hẹn dịch vụ
     * @param array $data - Dựa theo Request BookingRequest
     * @return ServiceReturn
     */
    public function bookService(
        array $data
    ): ServiceReturn
    {

        return $this->execute(
            callback: function () use ($data) {
                $userId = Auth::id();

                $resultValidate = $this->validateBooking($data, $userId);

                // Phải để status là pending rồi bắn vào queue để xử lý thanh toán
                $booking = $this->bookingRepository->create([
                    'user_id' => $userId,
                    'ktv_user_id' => $data['ktv_id'],
                    'category_id' => $data['category_id'],
                    'coupon_id' => $data['coupon_id'] ?? null,
                    'duration' => $resultValidate['categoryData']['option']['duration'],
                    'booking_time' => $resultValidate['bookTimeData']['booking_time'],
                    'start_time' => null,
                    'end_time' => null,
                    'status' => BookingStatus::PENDING->value,
                    'price' => $resultValidate['priceData']['price'],
                    'price_discount' => $resultValidate['priceData']['discount_coupon'],
                    'price_transportation' => $resultValidate['priceData']['price_distance'],
                    'payment_type' => PaymentType::BY_POINTS->value,
                    'note' => $data['note'] ?? '',
                    'address' => $data['address'] ?? '',
                    'latitude' => $data['latitude'] ?? 0,
                    'longitude' => $data['longitude'] ?? 0,
                    'ktv_address' => $resultValidate['ktvAddress']['address'] ?? '',
                    'ktv_latitude' => $resultValidate['ktvAddress']['latitude'] ?? 0,
                    'ktv_longitude' => $resultValidate['ktvAddress']['longitude'] ?? 0,
                ]);

                // xử lý giao dịch, ghi lại lịch sử dùng coupon
                WalletTransactionBookingJob::dispatch(
                    bookingId: $booking->id,
                    case: WalletTransCase::CONFIRM_BOOKING,
                );
                return [
                    'booking_id' => $booking->id,
                ];
            },
            useTransaction: true
        );
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
            $booking->cancel_by = $userCurrent->id == $booking->user_id ? UserRole::CUSTOMER->value : UserRole::KTV->value;
            $booking->save();

            return ServiceReturn::success(
                message: __("booking.waiting_cancel")
            );
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
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
        return $this->execute(function () use ($bookingId) {
            $booking = $this->bookingRepository->query()->find($bookingId);
            if (!$booking) {
                throw new ServiceException(
                    message: __("booking.not_found")
                );
            }
            $status = match ($booking->status) {
                BookingStatus::PENDING->value => 'waiting',
                BookingStatus::CONFIRMED->value => 'confirmed',
                BookingStatus::CANCELED->value, BookingStatus::PAYMENT_FAILED->value => 'failed',
                default => throw new ServiceException(
                    message: __("booking.not_found")
                ),
            };
            return [
                'status' => $status,
                'data' => [
                    'booking_id' => $booking->id,
                    'service_name' => $booking->service->name,
                    'date' => Carbon::make($booking->booking_time)->format('d/m/Y H:i'),
                    'location' => $booking->address,
                    'technician' => $booking->ktvUser->name,
                    'price' => $booking->price,
                    'price_discount' => $booking->price_discount,
                    'price_transportation' => $booking->price_transportation,
                    'total_price' => CalculatePrice::totalBookingPrice(
                        price: $booking->price,
                        priceDiscount: $booking->price_discount,
                        priceTransportation: $booking->price_transportation,
                    ),
                    'reason_cancel' => $booking->reason_cancel ?? '',
                ]
            ];
        });
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
        } catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(
                message: $exception->getMessage()
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
                case: WalletTransCase::FINISH_BOOKING,
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
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
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
     * Trả ra query dịch vụ có cùng category, KTV khác, service active
     * @param int $bookingId
     */
    public function findNearbyAvailableKtvs(int $bookingId)
    {
        return $this->execute(function () use ($bookingId) {
            $booking = $this->bookingRepository->query()->find($bookingId);
            if (!$booking) {
                throw new ServiceException(
                    message: __("booking.not_found")
                );
            }
            $lat = $booking->latitude;
            $lng = $booking->longitude;
            $categoryId = $booking->category_id;
            // Nếu booking không có toạ độ, dừng xử lý hoặc trả về mảng rỗng
            if (!$lat || !$lng) {
                return collect();
            }
            $radiusInMeters = 5000; // Bán kính 5km

            return $this->userRepository->query()
                // Join với bảng user_address để lấy địa chỉ chính
                ->join('user_address', function ($join) {
                    $join->on('users.id', '=', 'user_address.user_id')
                        ->where('user_address.is_primary', true);
                })
                // Lấy thông tin profile của KTV
                ->with('profile')
                // Chỉ lấy những KTV có role là KTV
                ->where('users.role', UserRole::KTV->value)
                ->where('users.id', '!=', $booking->ktv_user_id)
                // Điều kiện :Nằm trong bán kính 5km
                // Ép kiểu (cast) về ::geography để tính toán chính xác theo mét trên bề mặt Trái Đất
                ->whereRaw("
                    ST_DWithin(
                        ST_MakePoint(user_address.longitude, user_address.latitude)::geography,
                        ST_MakePoint(?, ?)::geography,
                        ?
                    )
                ", [$lng, $lat, $radiusInMeters])
                // Điều kiện: KTV phải có cung cấp dịch vụ (category_id) này
                ->whereHas('categories', function ($query) use ($categoryId) {
                    $query->where('categories.id', $categoryId);
                })

                // Điều kiện: KTV không có lịch nào đang ONGOING
                ->whereDoesntHave('ktvBookings', function ($query) {
                    $query->where('status', BookingStatus::ONGOING->value);
                })
                ->select('users.*')
                ->selectRaw("
                    ST_Distance(
                        ST_MakePoint(user_address.longitude, user_address.latitude)::geography,
                        ST_MakePoint(?, ?)::geography
                    ) AS distance_in_meters
                ", [$lng, $lat])
                // Ưu tiên hiển thị người ở gần nhất lên đầu
                ->orderBy('distance_in_meters', 'asc')
                ->limit(10)
                ->get();
        });
    }



    /**
     * Xử lý điều phối booking sang KTV khác
     * @param int $bookingId
     * @param int $newKtvId
     * @return ServiceReturn
     */
    public function handleReassignBooking(
        int  $bookingId,
        int $newKtvId
    ): ServiceReturn
    {
        return $this->execute(function () use ($bookingId,  $newKtvId) {
            // Tìm booking với lock để tránh race condition
            $booking = $this->bookingRepository->query()
                ->where('id', $bookingId)
                ->where('status', BookingStatus::WAITING_CANCEL->value)
                ->lockForUpdate()
                ->first();
            if (!$booking) {
                throw new ServiceException(__("error.booking_not_found_or_invalid_status"));
            }
            // Lưu thông tin KTV cũ để gửi notification
            $oldKtvId = $booking->ktv_user_id;
            // Cập nhật booking sang KTV mới
            $booking->ktv_user_id = $newKtvId;
            $booking->status = BookingStatus::CONFIRMED->value;
            $booking->reason_cancel = null; // Xóa lý do hủy
            $booking->cancel_by = null;
            $booking->save();

            // Gửi notification cho khách hàng
            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::BOOKING_REASSIGNED,
                data: [
                    'booking_id' => $booking->id,
                ]
            );

            // Gửi notification cho KTV mới
            SendNotificationJob::dispatch(
                userId: $newKtvId,
                type: NotificationType::NEW_BOOKING_REQUEST,
                data: [
                    'booking_id' => $booking->id,
                    'customer_name' => $booking->user->name ?? '',
                    'booking_time' => $booking->booking_time?->format('Y-m-d H:i:s'),
                ]
            );

            // Gửi notification cho KTV cũ
            if ($oldKtvId && $oldKtvId != $newKtvId) {
                SendNotificationJob::dispatch(
                    userId: $oldKtvId,
                    type: NotificationType::BOOKING_CANCELLED,
                    data: [
                        'booking_id' => $booking->id,
                        'reason' => __('booking.reassigned_to_other_ktv'),
                    ]
                );
            }

        });
    }

    /**
     *  --------- Protected methods ---------
     */

    /**
     * Kiểm tra dữ liệu book dịch vụ có hợp lệ không
     * Nếu phù hợp sẽ trả về data
     * @param array{
     *    category_id: int,
     *    option_id: int,
     *    ktv_id: int,
     *    latitude: float,
     *    longitude: float,
     *    coupon_id?: int|null
     *  } $data
     * @return array{
     *     categoryData: array{
     *         category: Category,
     *         option: array {
     *             'price': float,
     *             'duration': int,
     *         }
     *     },
     *     bookTimeData: array{
     *         booking_time: Carbon, // Thời gian book lịch (là hiện tại + break time),
     *         break_time: int, // Thời gian nghỉ giữa 2 lần phục vụ,
     *     },
     *     priceData: array{
     *           price: float, // Giá gốc của dịch vụ
     *          price_per_km: float, // Giá tiền/km
     *          distance: float, // Khoảng cách giữa khách hàng và KTV
     *          price_distance: float, // Giá di chuyển
     *          discount_coupon: float, // Giá trị giảm giá coupon
     *          final_price: float, // Giá cuối cùng (Total = Gía gốc + Giá di chuyển - Giá giảm giá coupon)
     *  },
     *     walletCustomer: Wallet,
     *     ktvAddress: UserAddress,
     * }
     * @throws ServiceException
     */
    protected function validateBooking(array $data, $userId): array
    {
        $user = $this->userRepository->queryUser()
            ->where('id', $userId)
            ->first();
        if (!$user) {
            throw new ServiceException(
                message: __("error.user_not_found")
            );
        }
        // Kiểm tra dịch vụ có phù hợp để book dịch vụ không
        $categoryData = $this->bookingValidator->validateServiceBooking(
            categoryId: $data['category_id'],
            ktvId: $data['ktv_id'],
            optionId: $data['option_id'],
        );

        // Kiểm tra kỹ thuật viên có thể book dịch vụ lúc này không
        $bookTimeData = $this->bookingValidator->validateKtvAvailabilityToBooking(
            ktvId: $data['ktv_id'],
            duration: $categoryData['option']['duration'],
            breakTime: $this->configService->getConfigValue(ConfigName::BREAK_TIME_GAP),
        );

        if (!empty($data['coupon_id'])) {
            // Kiểm tra Coupon tồn tại
            $coupon = $this->couponRepository->getCouponByIdOrFail(
                couponId: $data['coupon_id'],
            );
            if (!$coupon) {
                throw new ServiceException(
                    message: __("booking.coupon.not_found")
                );
            }
            // Kiểm tra Coupon có hợp lệ không
            $this->couponValidator->validateUseCoupon(
                coupon: $coupon,
                user: $user,
            );
        }


        // Lấy địa chỉ kỹ thuật viên
        $ktvAddress = $this->addressRepository->getPrimaryAddressByUserId($data['ktv_id']);
        if (!$ktvAddress) {
            throw new ServiceException(
                message: __("booking.service.not_found_location")
            );
        }

        // Lấy giá di chuyển / km
        $pricePerKm = $this->configService->getConfigValue(ConfigName::PRICE_TRANSPORTATION);
        // Tính toán giá
        $priceData = CalculatePrice::calculateBookingPrice(
            price: $categoryData['option']['price'],
            coupon: $coupon ?? null,
            pricePerKm: $pricePerKm,
            longitude: $data['longitude'],
            latitude: $data['latitude'],
            ktvLongitude: $ktvAddress->longitude,
            ktvLatitude: $ktvAddress->latitude,
        );

        // Kiểm tra số dư ví của khách hàng có đủ không
        $walletCustomer = $this->walletRepository->getWalletByUserId(
            userId: $userId,
        );
        if (!$walletCustomer) {
            throw new ServiceException(
                message: __("booking.payment.wallet_customer_not_found")
            );
        }
        $this->walletValidator->validateBookingBalance(
            wallet: $walletCustomer,
            price: $priceData['price'],
            priceDistance: $priceData['price_distance'],
            couponDiscount: $priceData['coupon_discount'] ?? 0,
        );


        // Trả ra data hợp lệ
        return [
            'categoryData' => $categoryData,
            'bookTimeData' => $bookTimeData,
            'priceData' => $priceData,
            'walletCustomer' => $walletCustomer,
            'ktvAddress' => $ktvAddress,
        ];
    }

}
