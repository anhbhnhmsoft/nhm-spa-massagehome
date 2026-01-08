<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\ConfigName;
use App\Enums\NotificationType;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserFileType;
use App\Enums\UserRole;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\SendNotificationJob;
use App\Repositories\BookingRepository;
use App\Repositories\CouponUserRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\UserAddressRepository;
use App\Repositories\UserFileRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserReviewApplicationRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserService extends BaseService
{
    public function __construct(
        protected UserRepository                  $userRepository,
        protected UserFileRepository              $userFileRepository,
        protected UserReviewApplicationRepository $userReviewApplicationRepository,
        protected UserProfileRepository           $userProfileRepository,
        protected WalletRepository                $walletRepository,
        protected UserAddressRepository           $userAddressRepository,
        protected BookingRepository               $bookingRepository,
        protected CouponUserRepository            $couponUserRepository,
        protected ConfigService                   $configService,
        protected WalletTransactionRepository     $walletTransactionRepository,
        protected ReviewRepository                $reviewRepository,
    ) {
        parent::__construct();
    }


    /**
     * Lấy thông tin dashboard của KTV
     * @return ServiceReturn
     */
    public function dashboardKtv()
    {
        try {
            $user = Auth::user();

            // 1. Chuẩn bị mốc thời gian (Dùng Carbon để chính xác và tận dụng Index DB tốt hơn)
            $todayStart = Carbon::today();
            $todayEnd = Carbon::today()->endOfDay();
            $yesterdayStart = Carbon::yesterday()->startOfDay();
            $yesterdayEnd = Carbon::yesterday()->endOfDay();

            // 2. Lấy Wallet (Check exists nhanh hơn nếu chỉ cần check, nhưng ở đây cần ID nên giữ nguyên)
            $wallet = $this->walletRepository->query()
                ->where('user_id', $user->id)
                ->select('id')
                ->first();

            if (!$wallet) {
                throw new ServiceException(__('error.wallet_not_found'));
            }

            // 3. TỐI ƯU 1: Gộp doanh thu Hôm nay & Hôm qua vào 1 Query
            // Thay vì 2 query, ta dùng SUM kết hợp CASE WHEN (hoặc IF trong MySQL)
            $revenueStats = $this->walletTransactionRepository->query()
                ->where('wallet_id', $wallet->id)
                ->where('type', WalletTransactionType::PAYMENT_FOR_KTV->value)
                ->where('status', WalletTransactionStatus::COMPLETED->value)
                ->where('created_at', '>=', $yesterdayStart) // Chỉ quét dữ liệu từ hôm qua đến nay (Tận dụng Index)
                ->toBase() // Bỏ qua việc hydrate Model để tăng tốc độ (trả về object thuần)
                ->selectRaw("SUM(CASE WHEN created_at >= ? THEN point_amount ELSE 0 END) as today", [$todayStart])
                ->selectRaw("SUM(CASE WHEN created_at < ? THEN point_amount ELSE 0 END) as yesterday", [$todayStart])
                ->first();

            // 4. TỐI ƯU 2: Gộp thống kê Booking (Completed & Pending) vào 1 Query
            $bookingStats = $this->bookingRepository->queryBooking()
                ->where('ktv_user_id', $user->id)
                ->whereBetween('booking_time', [$todayStart, $todayEnd]) // Tận dụng Index tốt hơn whereDate
                ->toBase()
                ->selectRaw("COUNT(CASE WHEN status = ? THEN 1 END) as completed", [BookingStatus::COMPLETED->value])
                ->selectRaw("COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as pending", [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value])
                ->first();

            // 5. Lấy booking sắp tới (hoặc mới nhất)
            // Lưu ý: Nếu là "sắp tới" thì nên dùng 'asc' và điều kiện >= now().
            // Nhưng tôi giữ nguyên logic 'desc' của bạn.
            $booking = $this->bookingRepository->queryBooking()
                ->where('ktv_user_id', $user->id)
                ->whereIn('status', [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value])
                ->orderBy('booking_time', 'desc')
                ->first();

            // 6. Review mới nhất hôm nay
            $reviewToday = $this->reviewRepository->queryReview()
                ->where('user_id', $user->id)
                ->whereBetween('review_at', [$todayStart, $todayEnd])
                ->orderBy('review_at', 'desc')
                ->get();

            return ServiceReturn::success(
                data: [
                    'booking' => $booking,
                    'total_revenue_today' => (float)($revenueStats->today ?? 0),
                    'total_revenue_yesterday' => (float)($revenueStats->yesterday ?? 0),
                    'total_booking_completed_today' => (int)($bookingStats->completed ?? 0),
                    'total_booking_pending_today' => (int)($bookingStats->pending ?? 0),
                    'review_today' => $reviewToday,
                ]
            );
        } catch (ServiceException $e) {
            return ServiceReturn::error($e->getMessage());
        } catch (\Exception $e) {
            LogHelper::error("Lỗi UserService@dashboardKtv", $e);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Lấy thông tin dashboard profile của user hiện tại
     * @return ServiceReturn
     */
    public function dashboardProfile()
    {
        try {
            $user = Auth::user();
            // Số dư wallet
            $wallet = $this->walletRepository->query()
                ->where('user_id', $user->id)
                ->first();
            $walletBalance = $wallet?->balance ?? "0";


            // Lấy số lượng đặt lịch theo từng trạng thái
            $bookingRawCounts = $this->bookingRepository->query()
                ->where('user_id', $user->id)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');
            $bookingCount = collect(BookingStatus::cases())
                ->mapWithKeys(function ($case) use ($bookingRawCounts) {
                    return [$case->value => $bookingRawCounts->get($case->value, 0)];
                })
                ->toArray();

            // Số lượng mã giảm giá
            $couponUserCount = $this->couponUserRepository->query()
                ->where('user_id', $user->id)
                ->where('is_used', false)
                ->count();

            return ServiceReturn::success(
                data: [
                    'booking_count' => $bookingCount,
                    'wallet_balance' => $walletBalance,
                    'coupon_user_count' => $couponUserCount,
                ]
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi UserService@dashboardCustomer",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __('common_error.server_error')
            );
        }
    }


    /**
     * Lấy danh sách KTV
     * @param FilterDTO $dto
     * @return ServiceReturn
     */
    public function paginationKTV(FilterDTO $dto): ServiceReturn
    {
        try {
            $query = $this->userRepository->queryKTV();
            $query = $this->userRepository->filterQuery(
                query: $query,
                filters: $dto->filters
            );
            $query = $this->userRepository->sortQuery(
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
                message: "Lỗi UserService@pagination",
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
     * Lấy thông tin KTV theo ID
     * @param int $id
     * @return ServiceReturn
     */
    public function getKtvById(int $id): ServiceReturn
    {
        try {
            $breakTimeGapReturn = $this->configService->getConfig(ConfigName::BREAK_TIME_GAP);
            if ($breakTimeGapReturn->isError()) {
                return ServiceReturn::error(
                    message: __("booking.break_time_gap.not_found")
                );
            }
            $breakTimeGap = $breakTimeGapReturn->getData();
            $ktv = $this->userRepository->queryKTV()
                ->with([
                    'files' => function ($query) {
                        $query->whereIn('type', [UserFileType::KTV_IMAGE_DISPLAY->value]);
                    },
                    // Chỉ lấy 1 review
                    'reviewsReceived' => function ($query) {
                        $query->where('hidden', false)
                            ->latest('created_at')
                            ->limit(1);
                    },
                    // Lấy lịch hẹn cuối cùng mà KTV này thực hiện hoặc đang diễn ra
                    'ktvBookings' => function ($query) {
                        $query->whereIn('status', [BookingStatus::CONFIRMED->value, BookingStatus::ONGOING->value])
                            ->latest('booking_time')
                            ->limit(1);
                    }
                ])
                ->find($id);
            if (!$ktv) {
                throw new ServiceException(
                    message: __("common_error.data_not_found")
                );
            }
            return ServiceReturn::success(
                data: [
                    'ktv' => $ktv,
                    'break_time_gap' => $breakTimeGap['config_value'],
                ]
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi UserService@getKtvById",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Upload file và trả về đường dẫn lưu trên storage.
     */
    public function uploadTempFile(UploadedFile $file, ?int $type = null, bool $isPublic = false): ServiceReturn
    {
        try {
            $disk = $isPublic ? 'public' : 'private';
            $path = Storage::disk($disk)->put('uploads', $file);

            return ServiceReturn::success(data: [
                'file_path' => $path,
                'disk' => $disk,
                'is_public' => $isPublic,
                'type' => $type,
            ]);
        } catch (\Throwable $exception) {
            LogHelper::error(
                message: "Lỗi UserService@uploadTempFile",
                ex: $exception
            );

            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    public function makeNewApplyKTV(array $data)
    {
        DB::beginTransaction();
        try {
            $userCheck = $this->userRepository->query()->where('phone', $data['phone'])->first();
            if ($userCheck) {
                throw new ServiceException(
                    message: __("common_error.data_exists")
                );
            }

            $userInitial = $this->userRepository->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => UserRole::KTV->value,
                'phone_verified_at' => now(),
                'is_active' => false,
            ]);

            $userReviewApplication = $this->userReviewApplicationRepository->create([
                'user_id' => $userInitial->id,
                'agency_id' => optional($data['reviewApplication'])['agency_id'] ?? null,
                'status' => ReviewApplicationStatus::PENDING->value,
                'province_code' => optional($data['reviewApplication'])['province']['name'] ?? null,
                'address' => optional($data['reviewApplication'])['address'] ?? null,
                'experience' => optional($data['reviewApplication'])['experience'] ?? null,
                'application_date' => now(),
                'bio' => optional($data['reviewApplication'])['bio'] ?? null
            ]);

            $userProfile = $this->userProfileRepository->create([
                'avatar_url' => optional($data['profile'])['avatar_url'] ?? null,
                'user_id' => $userInitial->id,
                'gender' => optional($data['profile'])['gender'] ?? null,
                'date_of_birth' => optional($data['profile'])['date_of_birth'] ?? null,
                'bio' => optional($data['profile'])['bio'] ?? null
            ]);

            $wallet = $this->walletRepository->create([
                'user_id' => $userInitial->id,
                'balance' => 0,
                'is_active' => false
            ]);

            foreach ($data['files'] as $file) {
                $this->userFileRepository->create([
                    'user_id' => $userInitial->id,
                    'type' => optional($file)['type'] ?? null,
                    'file_path' => optional($file)['file_path'] ?? null
                ]);
            }
            DB::commit();
            return ServiceReturn::success(
                data: $userInitial->load('reviewApplication', 'profile', 'files')
            );
        } catch (ServiceException $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi UserService@makeNewApplyKTV",
                ex: $exception
            );
            foreach ($data['files'] as $file) {
                Storage::delete($file['file_path']);
            }
            Storage::delete($data['profile']['avatar_url']);
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi UserService@makeNewApplyKTV",
                ex: $exception
            );
            foreach ($data['files'] as $file) {
                Storage::delete($file['file_path']);
            }
            Storage::delete($data['profile']['avatar_url']);
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Lấy file theo path, sử dụng cơ chế cache.
     * @param string $path
     * @return ServiceReturn
     */
    public function getUserFile(string $path): ServiceReturn
    {
        $uniqueKey = hash('sha256', $path);

        try {
            $file = Caching::getCache(
                key: CacheKey::CACHE_USER_FILE,
                uniqueKey: $uniqueKey
            );
            if ($file) {
                return ServiceReturn::success(
                    data: $file
                );
            }
            $file = $this->userFileRepository->query()->where('file_path', $path)->first();
            if (!$file) {
                throw new ServiceException(
                    message: __("common_error.data_not_found")
                );
            }
            Caching::setCache(
                key: CacheKey::CACHE_USER_FILE,
                uniqueKey: $uniqueKey,
                value: $file,
                expire: 60
            );

            return ServiceReturn::success(
                data: $file
            );
        } catch (ServiceException $exception) {
            LogHelper::error(
                message: "Lỗi UserService@getUserFile",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.data_not_found")
            );
        }
    }

    public function activeStaffApply(int $id)
    {
        DB::beginTransaction();
        try {
            $user = $this->userRepository->query()->where('id', $id)->first();
            if (!$user) {
                throw new ServiceException(
                    message: __("common_error.data_not_found")
                );
            }
            $user->is_active = true;
            $apply = $user->reviewApplication;
            if ($apply) {
                $apply->status = ReviewApplicationStatus::APPROVED;
                $apply->effective_date = now();
                $user->role = $apply->role;
                $apply->save();
                $user->save();
            }

            SendNotificationJob::dispatch(
                userId: $user->id,
                type: NotificationType::STAFF_APPLY_SUCCESS,
                data: [
                    'user_id' => $user->id,
                    'role' => $user->role,
                ]
            );
            if ($user->wallet) {
                $user->wallet->is_active = true;
                $user->wallet->save();
            } else {
                $this->walletRepository->create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'is_active' => true
                ]);
            }
            DB::commit();
            return ServiceReturn::success(
                message: __("common.success.data_updated")
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi UserService@activeKTVapply",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    public function rejectStaffApply(int $id, string $note)
    {
        DB::beginTransaction();
        try {
            $user = $this->userRepository->query()->where('id', $id)->first();
            if (!$user) {
                throw new ServiceException(
                    message: __("common_error.data_not_found")
                );
            }
            $apply = $user->reviewApplication;
            if ($apply) {
                $apply->status = ReviewApplicationStatus::REJECTED;
                $apply->note = $note;
                $apply->effective_date = now();
                $apply->save();
            }

            SendNotificationJob::dispatch(
                userId: $user->id,
                type: NotificationType::STAFF_APPLY_REJECTED,
                data: [
                    'user_id' => $user->id,
                    'role' => $user->role,
                ]
            );
            DB::commit();
            return ServiceReturn::success(
                message: __("common.success.data_updated")
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi UserService@rejectKTVapply",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }
    /**
     * Đăng ký làm đối tác cho user hiện tại (không tạo user mới).
     * - Tạo hoặc cập nhật bản ghi review_application với trạng thái CHỜ DUYỆT.
     * - Gắn các file hồ sơ vào user hiện tại.
     *
     * @param array $data
     * @return ServiceReturn
     */
    public function applyPartnerForCurrentUser(array $data): ServiceReturn
    {
        try {
            DB::beginTransaction();

            /** @var \App\Models\User $user */
            $user = Auth::user();
            if (!$user) {
                throw new ServiceException(
                    message: __("common_error.unauthenticated")
                );
            }

            // Cập nhật tên nếu có truyền lên
            if (!empty($data['name'])) {
                $user->name = $data['name'];
                $user->save();
            }

            $applyRole = !empty($data['role']) ? (int)$data['role'] : null;
            if (!$applyRole || !in_array($applyRole, [UserRole::KTV->value, UserRole::AGENCY->value])) {
                throw new ServiceException(
                    message: __("common_error.invalid_data")
                );
            }

            $reviewData = [
                'user_id' => $user->id,
                'agency_id' => optional($data['reviewApplication'])['agency_id'] ?? null,
                'status' => ReviewApplicationStatus::PENDING->value,
                'province_code' => optional($data['reviewApplication'])['province_code'] ?? null,
                'address' => optional($data['reviewApplication'])['address'] ?? null,
                'latitude' => optional($data['reviewApplication'])['latitude'] ?? null,
                'longitude' => optional($data['reviewApplication'])['longitude'] ?? null,
                'application_date' => now(),
                'bio' => optional($data['reviewApplication'])['bio'] ?? null,
                'role' => $applyRole,
            ];

            $existingReview = $this->userReviewApplicationRepository
                ->query()
                ->where('user_id', $user->id)
                ->first();

            if ($existingReview) {
                $existingReview->update($reviewData);
            } else {
                $this->userReviewApplicationRepository->create($reviewData);
            }

            if (!empty($data['files']) && is_array($data['files'])) {
                foreach ($data['files'] as $file) {
                    $this->userFileRepository->create([
                        'user_id' => $user->id,
                        'type' => optional($file)['type'] ?? null,
                        'file_path' => optional($file)['file_path'] ?? null,
                        'is_public' => (bool)(optional($file)['is_public'] ?? false),
                        'role' => $applyRole,
                    ]);
                }
            }

            DB::commit();

            return ServiceReturn::success(
                data: $user->load('reviewApplication', 'files'),
                message: __("common.success.data_created")
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi UserService@applyPartnerForCurrentUser",
                ex: $exception
            );

            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    public function makeNewApplyAgency(array $data)
    {
        DB::beginTransaction();
        try {
            $userCheck = $this->userRepository->query()->where('phone', $data['phone'])->first();
            if ($userCheck) {
                throw new ServiceException(
                    message: __("common_error.data_exists")
                );
            }

            $userInitial = $this->userRepository->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => UserRole::AGENCY->value,
                'phone_verified_at' => now(),
                'is_active' => false,
            ]);

            $userReviewApplication = $this->userReviewApplicationRepository->create([
                'user_id' => $userInitial->id,
                'status' => ReviewApplicationStatus::PENDING->value,
                'province_code' => optional($data['reviewApplication'])['province_code'] ?? null,
                'address' => optional($data['reviewApplication'])['address'] ?? null,
                'application_date' => now(),
                'bio' => optional($data['reviewApplication'])['bio'] ?? null
            ]);

            // $userProfile = $this->userProfileRepository->create([
            //     'avatar_url' => optional($data['profile'])['avatar_url'] ?? null,
            //     'user_id' => $userInitial->id,
            //     'gender' => optional($data['profile'])['gender'] ?? null,
            //     'date_of_birth' => optional($data['profile'])['date_of_birth'] ?? null,
            //     'bio' => optional($data['profile'])['bio'] ?? null
            // ]);

            // $wallet = $this->walletRepository->create([
            //     'user_id' => $userInitial->id,
            //     'balance' => 0,
            //     'is_active' => false
            // ]);

            foreach ($data['files'] as $file) {
                $this->userFileRepository->create([
                    'user_id' => $userInitial->id,
                    'type' => optional($file)['type'] ?? null,
                    'file_path' => optional($file)['file_path'] ?? null
                ]);
            }

            DB::commit();
            return ServiceReturn::success(
                data: $userInitial->load('reviewApplication', 'files'),
                message: __("common.success.data_created")
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi UserService@makeNewApplyAgency",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    public function updateUser(array $data)
    {
        DB::beginTransaction();
        try {
            $user = $this->userRepository->query()->where('id', $data['id'])->first();
            if (!$user) {
                throw new ServiceException(
                    message: __("common_error.data_not_found")
                );
            }
            $dataUpdate = [];
            if (isset($data['name']) && $data['name']) {
                $dataUpdate['name'] = $data['name'];
            }
            if (isset($data['phone']) && $data['phone']) {
                $dataUpdate['phone'] = $data['phone'];
            }
            if (isset($data['password']) && $data['password']) {
                $dataUpdate['password'] = $data['password'];
            }
            if (isset($data['role']) && $data['role']) {
                $dataUpdate['role'] = $data['role'];
            }
            if (isset($data['is_active']) && $data['is_active']) {
                $dataUpdate['is_active'] = $data['is_active'];
            }
            $user->update($dataUpdate);

            if (isset($data['profile'])) {
                $user->profile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $data['profile']
                );
            }

            if (isset($data['files'])) {
                foreach ($data['files'] as $file) {
                    $this->userFileRepository->create([
                        'user_id' => $user->id,
                        'type' => optional($file)['type'] ?? null,
                        'file_path' => optional($file)['file_path'] ?? null
                    ]);
                }
            }

            $user->reviewApplication()->updateOrCreate(
                ['user_id' => $user->id],
                $data['reviewApplication']
            );
            DB::commit();
            return ServiceReturn::success(
                data: $user->load('reviewApplication', 'files', 'profile'),
                message: __("common.success.data_updated")
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi UserService@updateUser",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Save user address
     * @param array $data
     * @return ServiceReturn
     */
    public function saveAddress(array $data): ServiceReturn
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $checkAddress = $this->userAddressRepository->query()
                ->where('user_id', $user->id)
                ->where('latitude', $data['latitude'])
                ->where('longitude', $data['longitude'])
                ->first();
            if ($checkAddress) {
                return ServiceReturn::error(
                    message: __("common_error.address_exists")
                );
            }
            $isPrimary = $data['is_primary'] ?? false;
            $preparedData = [
                'user_id' => $user->id,
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'desc' => $data['desc'] ?? '',
                'is_primary' => $isPrimary
            ];
            $userAddress = $this->userAddressRepository->create($preparedData);
            // Nếu là địa chỉ chính thì cập nhật các địa chỉ khác thành không phải chính
            if ($isPrimary) {
                $this->userAddressRepository->query()
                    ->where('user_id', $user->id)
                    ->where('id', '<>', $userAddress->id)
                    ->update(['is_primary' => false]);
            }
            DB::commit();
            return ServiceReturn::success(
                data: $userAddress,
                message: __("common.success.data_created")
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi UserService@saveAddress",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Edit user address
     * @param  $id
     * @param array $data
     * @return ServiceReturn
     */
    public function editAddress($id, array $data): ServiceReturn
    {
        try {
            $user = Auth::user();
            $userAddress = $this->userAddressRepository->query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$userAddress) {
                throw new ServiceException(__("error.address_not_found"));
            }
            $isPrimary = $data['is_primary'] ?? $userAddress->is_primary;
            $preparedData = [
                'address' => $data['address'] ?? $userAddress->address,
                'latitude' => $data['latitude'] ?? $userAddress->latitude,
                'longitude' => $data['longitude'] ?? $userAddress->longitude,
                'desc' => $data['desc'] ?? $userAddress->desc,
                'is_primary' => $isPrimary
            ];
            $userAddress->update($preparedData);
            // Nếu là địa chỉ chính thì cập nhật các địa chỉ khác thành không phải chính
            if ($isPrimary) {
                $this->userAddressRepository->query()
                    ->where('user_id', $user->id)
                    ->where('id', '<>', $userAddress->id)
                    ->update(['is_primary' => false]);
            }
            return ServiceReturn::success(
                data: $userAddress,
                message: __("common.success.data_updated")
            );
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi UserService@editAddress",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Delete user address
     * @param $id
     * @return ServiceReturn
     */
    public function deleteAddress($id): ServiceReturn
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $userAddress = $this->userAddressRepository->query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            if (!$userAddress) {
                throw new ServiceException(__("error.address_not_found"));
            }
            // kiểm tra nếu địa chỉ xóa là chính thì set 1 địa chỉ khác thành chính
            if ($userAddress->is_primary) {
                $anotherAddress = $this->userAddressRepository->query()
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->where('id', '<>', $userAddress->id)
                    ->first();
                if ($anotherAddress) {
                    $anotherAddress->is_primary = true;
                    $anotherAddress->save();
                }
            }
            // Sau đó mới xóa địa chỉ
            $userAddress->delete();
            DB::commit();
            return ServiceReturn::success(
                message: __("common.success.data_deleted")
            );
        } catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi UserService@deleteAddress",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Get paginate user address
     * @param FilterDTO $dto
     * @return ServiceReturn
     */
    public function getPaginateAddress(FilterDTO $dto): ServiceReturn
    {
        try {
            $query = $this->userAddressRepository->query();
            $query = $this->userAddressRepository->filterQuery(
                query: $query,
                filters: $dto->filters
            );
            $query = $this->userAddressRepository->sortQuery(
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
                message: "Lỗi UserService@getPaginateAddress",
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

    public function updateKtvProfile(array $data): ServiceReturn
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            // 1. Update Password if old_pass provided
            if (!empty($data['old_pass'])) {
                if (!\Illuminate\Support\Facades\Hash::check($data['old_pass'], $user->password)) {
                    // Fix: return ServiceReturn object directly
                    return ServiceReturn::error(__('auth.error.wrong_password'));
                }
                $user->update(['password' => $data['new_pass']]);
            }

            // 2. Update UserReviewApplication
            $reviewApp = $user->getStaffReviewsAttribute()->first();
            if ($reviewApp) {
                $updateData = [];
                if (isset($data['bio'])) $updateData['bio'] = $data['bio'];
                if (isset($data['experience'])) $updateData['experience'] = $data['experience'];
                if (isset($data['lat'])) $updateData['latitude'] = (float) $data['lat'];
                if (isset($data['lng'])) $updateData['longitude'] = (float) $data['lng'];
                if (isset($data['address'])) $updateData['address'] = $data['address'];

                if (!empty($updateData)) {
                    $this->userReviewApplicationRepository->update($reviewApp->id, $updateData);
                }
            }else {
                $updateData = [];
                if (isset($data['bio'])) $updateData['bio'] = $data['bio'];
                if (isset($data['experience'])) $updateData['experience'] = $data['experience'];
                if (isset($data['lat'])) $updateData['latitude'] = (float) $data['lat'];
                if (isset($data['lng'])) $updateData['longitude'] = (float) $data['lng'];
                if (isset($data['address'])) $updateData['address'] = $data['address'];
                if (!empty($updateData)) {
                    $updateData['user_id'] = $user->id;
                    $updateData['status'] = ReviewApplicationStatus::PENDING->value;
                    $updateData['role'] = UserRole::KTV->value;
                    $this->userReviewApplicationRepository->create($updateData);
                }
            }

            // 3. Update UserProfile
            $profile = $user->profile;
            if ($profile) {
                $updateProfile = [];
                if (isset($data['gender'])) $updateProfile['gender'] = $data['gender'];
                if (isset($data['date_of_birth'])) $updateProfile['date_of_birth'] = $data['date_of_birth'];

                if (!empty($updateProfile)) {
                    $profile->update($updateProfile);
                    $profile->save();
                }
            }else {
                $updateProfile = [];
                if (isset($data['gender'])) $updateProfile['gender'] = $data['gender'];
                if (isset($data['date_of_birth'])) $updateProfile['date_of_birth'] = $data['date_of_birth'];

                if (!empty($updateProfile)) {
                    $updateProfile['user_id'] = $user->id;
                    $this->userProfileRepository->create($updateProfile);
                }
                $profile = $this->userProfileRepository->find($user->id);
            }
            $user->save();
            // Reload user with relations to return fresh data
            $user->load(['profile', 'reviewApplication']);

            return ServiceReturn::success(
                data: $user,
                message: __("common.success.data_updated")
            );
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi UserService@updateKtvProfile",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Link KTV to Agency via QR
     * @param int $ktvId
     * @param int $agencyId
     * @return ServiceReturn
     */
    public function linkKtvToAgency(int $ktvId, int $agencyId): ServiceReturn
    {
        try {
            $ktv = $this->userRepository->query()->find($ktvId);
            $agency = $this->userRepository->query()->find($agencyId);

            if (!$ktv || !$agency) {
                return ServiceReturn::error(__('common_error.data_not_found'));
            }

            // Check roles
            if ($ktv->role !== UserRole::KTV->value) {
                return ServiceReturn::success( data: ['is_ktv' => false], message:  __('common_error.invalid_role'));
            }

            if ($agency->role !== UserRole::AGENCY->value) {
                return ServiceReturn::error(__('common_error.invalid_role'));
            }

            // Prevent self-linking
            if ($ktv->id === $agency->id) {
                return ServiceReturn::error(__('common_error.cannot_link_self'));
            }

            // Update link
            $ktv->referred_by_user_id = $agency->id;
            $ktv->save();

            return ServiceReturn::success(
                data: [
                    'ktv' => $ktv,
                    'is_ktv' => true,
                ],
                message: __('common.success.data_updated')
            );
        } catch (\Exception $e) {
            LogHelper::error('Lỗi UserService@linkKtvToAgency', $e);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }
}
