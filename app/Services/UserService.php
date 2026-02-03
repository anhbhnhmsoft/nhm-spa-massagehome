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
use App\Enums\ConfigName;
use App\Enums\ContractFileType;
use App\Enums\DirectFile;
use App\Enums\Jobs\WalletTransCase;
use App\Enums\KTVConfigSchedules;
use App\Enums\NotificationAdminType;
use App\Enums\NotificationType;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserFileType;
use App\Enums\UserRole;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\SendNotificationJob;
use App\Jobs\WalletTransactionJob;
use App\Repositories\BookingRepository;
use App\Repositories\CouponUserRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\StaticContractRepository;
use App\Repositories\UserAddressRepository;
use App\Repositories\UserFileRepository;
use App\Repositories\UserKtvScheduleRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserReviewApplicationRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Services\WalletService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        protected UserKtvScheduleRepository       $userKtvScheduleRepository,
        protected StaticContractRepository        $staticContractRepository,
        protected WalletService                   $walletService,
        protected NotificationService             $notificationService,
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
            $revenueStats = $this->bookingRepository->query()
                ->where('ktv_user_id', $user->id)
                ->where('status', BookingStatus::COMPLETED->value)
                ->where('booking_time', '>=', $yesterdayStart) // Chỉ quét dữ liệu từ hôm qua đến nay (Tận dụng Index)
                ->toBase() // Bỏ qua việc hydrate Model để tăng tốc độ (trả về object thuần)
                ->selectRaw("SUM(CASE WHEN booking_time >= ? THEN price ELSE 0 END) as today", [$todayStart])
                ->selectRaw("SUM(CASE WHEN booking_time < ? THEN price ELSE 0 END) as yesterday", [$todayStart])
                ->first();

            // 4. TỐI ƯU 2: Gộp thống kê Booking (Completed & Pending) vào 1 Query
            $bookingStats = $this->bookingRepository->query()
                ->where('ktv_user_id', $user->id)
                ->whereBetween('booking_time', [$todayStart, $todayEnd]) // Tận dụng Index tốt hơn whereDate
                ->toBase()
                ->selectRaw("COUNT(CASE WHEN status = ? THEN 1 END) as completed", [BookingStatus::COMPLETED->value])
                ->selectRaw("COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as pending", [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value])
                ->first();

            // 5. Lấy booking sắp tới (hoặc mới nhất)
            $booking = $this->bookingRepository->query()
                ->where('ktv_user_id', $user->id)
                ->whereIn('status', [
                    BookingStatus::PENDING->value,
                    BookingStatus::CONFIRMED->value
                ])
                ->where('booking_time', '>=', $todayStart)
                ->where('booking_time', '<=', $todayEnd)
                ->orderBy('booking_time', 'asc')
                ->first();

            // 6. Lấy booking đang diễn ra
            $bookingOnGoing = $this->bookingRepository->query()
                ->where('ktv_user_id', $user->id)
                ->where('status', BookingStatus::ONGOING->value)
                ->first();


            // 7. Review mới nhất hôm nay
            $reviewToday = $this->reviewRepository->query()
                ->with('reviewer')
                ->where('user_id', $user->id)
                ->whereBetween('review_at', [$todayStart, $todayEnd])
                ->orderBy('review_at', 'desc')
                ->get();

            return ServiceReturn::success(
                data: [
                    'booking' => $booking,
                    'booking_ongoing' => $bookingOnGoing,
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
            // Lấy config khoảng thời gian nghỉ giữa 2 buổi
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
                            // Chỉ lấy lịch hôm nay
                            ->whereDate('booking_time', date('Y-m-d'))
                            ->limit(1);
                    },
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

    /**
     * Duyệt hồ sơ KTV
     * @param int $id
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function activeStaffApply(int $id): ServiceReturn
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
            }else{
                throw new ServiceException(
                    message: __("common_error.data_not_found")
                );
            }
            // Tạo lịch làm việc mặc định cho KTV
             if ($apply->role === UserRole::KTV->value) {
                $user->schedule()->create([
                    'is_working' => true,
                    'working_schedule' => KTVConfigSchedules::getDefaultSchema(),
                ]);
            }
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


            // Gửi thông báo
            SendNotificationJob::dispatch(
                userId: $user->id,
                type: NotificationType::STAFF_APPLY_SUCCESS,
                data: [
                    'user_id' => $user->id,
                    'role' => $user->role,
                ]
            );

            // Trả tiền thưởng cho người giới thiệu nếu có referrer_id và role = KTV
            if (!empty($apply->referrer_id) && $apply->role == UserRole::KTV->value) {
                WalletTransactionJob::dispatch(
                    data: [
                        'referral_id' => $apply->referrer_id,
                        'user_id' => $user->id
                    ],
                    case: WalletTransCase::REWARD_FOR_KTV_REFERRAL
                );
            }

            return ServiceReturn::success(
                message: __("common.success.data_updated")
            );
        } catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
        catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi UserService@activeStaffApply",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
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
                $user->save();
            }else{
                throw new ServiceException(
                    message: __("common_error.data_not_found")
                );
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
        }catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
        catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi UserService@rejectStaffApply",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }


    /**
     * Đăng ký làm đối tác cho user hiện tại
     * @param array $data
     * @return ServiceReturn
     */
    public function applyPartnerForCurrentUser(array $data): ServiceReturn
    {
        $tempFiles = [];
        try {
            DB::beginTransaction();
            $user = Auth::user();
            if (!$user) {
                throw new ServiceException(
                    message: __("common_error.unauthenticated")
                );
            }

            // Kiểm tra xem user đã có review application chưa
            $existingReview = $this->userReviewApplicationRepository
                ->query()
                ->where('user_id', $user->id)
                ->first();
            // Nếu Khách hàng đã có review application thì không thể đăng ký lại
            if ($existingReview) {
                // Nếu review application bị từ chối thì không thể đăng ký lại
                if ($existingReview->status == ReviewApplicationStatus::REJECTED->value){
                    throw new ServiceException(
                        message: __("error.user_have_review_application_rejected")
                    );
                }else{
                    throw new ServiceException(
                        message: __("error.user_have_review_application")
                    );
                }
            }

            // Kiểm tra referrer_id có tồn tại
            if (!empty($data['referrer_id'])) {
                $agency = $this->userRepository
                    ->queryUser()
                    ->where('id', $data['referrer_id'])
                    ->whereIn('role', [UserRole::AGENCY->value, UserRole::KTV->value])
                    ->first();
                if (!$agency) {
                    throw new ServiceException(
                        message: __("error.referrer_not_found")
                    );
                }
            }

            $reviewData = [
                'user_id' => $user->id,
                'referrer_id' => $data['referrer_id'] ?? null,
                'status' => ReviewApplicationStatus::PENDING->value,
                'province_code' => $data['province_code'],
                'nickname' => $data['nickname'] ?? null,
                'address' => $data['address'],
                'experience' => $data['experience'] ?? 0,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'application_date' => now(),
                'role' => $data['role'],
            ];
            $reviewData['bio'] = Helper::multilingualPayload($data, 'bio');

            // Kiểm tra nếu user dki làm và role = KTV thì set is_leader = true
            if (isset($data['is_leader']) && $data['role'] == UserRole::KTV->value) {
                $reviewData['is_leader'] = true;
            }

            // Lưu review application
            $this->userReviewApplicationRepository->create($reviewData);

            // Lưu file uploads
            foreach ($data['file_uploads'] as $fileUpload) {
                $typeUpload = $fileUpload['type_upload'];
                $file = $fileUpload['file'];
                // Kiểm tra file có phải là instance của UploadedFile không
                if (!$file instanceof UploadedFile) {
                    throw new ServiceException(
                        message: __("common_error.invalid_data")
                    );
                }
                $isPublic = !in_array($typeUpload, UserFileType::getTypeUploadToPrivateDisk());

                $path = $file->store(DirectFile::makePathById(
                    type: DirectFile::USER_FILE_UPLOAD,
                    id: $user->id
                ), $isPublic ? 'public' : 'private');
                $tempFiles[] = [
                    'disk' => $isPublic ? 'public' : 'private',
                    'path' => $path,
                ];
                $this->userFileRepository->create([
                    'user_id' => $user->id,
                    'type' => $typeUpload,
                    'file_path' => $path,
                    'is_public' => $isPublic,
                    'role' => $data['role'],
                ]);
            }

            // Gửi thông báo cho quản trị viên
            if ($data['role'] == UserRole::KTV->value) {
                $this->notificationService->sendAdminNotification(
                    type: NotificationAdminType::USER_APPLY_KTV_PARTNER,
                    data: [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'phone' => $user->phone,
                    ]
                );
            }
            // Thông báo đăng ký làm đối tác đại lý
            else if ($data['role'] == UserRole::AGENCY->value) {
                $this->notificationService->sendAdminNotification(
                    type: NotificationAdminType::USER_APPLY_AGENCY_PARTNER,
                    data: [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'phone' => $user->phone,
                    ]
                );
            }
            DB::commit();


            return ServiceReturn::success(
                data: $user->load('reviewApplication', 'files'),
                message: __("common.success.data_created")
            );
        }
        catch (ServiceException $exception) {
            DB::rollBack();
            foreach ($tempFiles as $file) {
                Storage::disk($file['disk'])->delete($file['path']);
            }
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
        catch (\Throwable $exception) {
            DB::rollBack();
            foreach ($tempFiles as $file) {
                Storage::disk($file['disk'])->delete($file['path']);
            }
            LogHelper::error(
                message: "Lỗi UserService@applyPartnerForCurrentUser",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Kiểm tra người dùng hiện tại đã nộp đơn ứng tuyển chưa ?
     * @return ServiceReturn
     */
    public function checkApplyPartnerForCurrentUser(): ServiceReturn
    {
        $user = Auth::user();
        try {
            $checkApply = $this->userReviewApplicationRepository->query()
                ->where('user_id', $user->id)
                ->first();
            if (!$checkApply) {
                return ServiceReturn::success(
                    data: [
                        'can_apply' => true,
                    ]
                );
            }
            return ServiceReturn::success(
                data: [
                    'can_apply' => false,
                    'apply_role' => $checkApply->role,
                    'apply_status' => $checkApply->status,
                ]
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi UserService@checkApplyPartnerForCurrentUser",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
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

    /**
     * Cập nhật hồ sơ KTV
     * @param array $data
     * @return ServiceReturn
     */
    public function updateKtvProfile(array $data): ServiceReturn
    {
        DB::beginTransaction();
        try {
            $user = $this->userRepository->queryUser()
                ->where('id', $data['user_id'])
                ->where('role', UserRole::KTV->value)
                ->first();
            if (!$user) {
                throw new ServiceException(__("error.user_not_found"));
            }
            // 1. Update Password if old_pass provided
            if (!empty($data['old_pass'])) {
                if (!Hash::check($data['old_pass'], $user->password)) {
                    // Fix: return ServiceReturn object directly
                    return ServiceReturn::error(__('auth.error.wrong_password'));
                }
                $user->update(['password' => Hash::make($data['new_pass'])]);
            }

            // 2. Update UserReviewApplication
            $reviewApp = $user->getStaffReviewsAttribute()->first();
            if (!$reviewApp) {
                throw new ServiceException(__("error.user_not_found"));
            }
            $updateData = [];
            if (isset($data['bio'])){
                $updateData['bio'] = Helper::multilingualPayload($data, 'bio');
            }
            if (isset($data['experience'])) $updateData['experience'] = $data['experience'];
            if (isset($data['lat'])) $updateData['latitude'] = (float) $data['lat'];
            if (isset($data['lng'])) $updateData['longitude'] = (float) $data['lng'];
            if (isset($data['address'])) $updateData['address'] = $data['address'];
            if (!empty($updateData)) {
                $this->userReviewApplicationRepository->update($reviewApp->id, $updateData);
            }

            // 3. Update UserProfile
            $profile = $user->profile;
            if (!$profile) {
                throw new ServiceException(__("error.user_not_found"));
            }
            $updateProfile = [];
            if (isset($data['gender'])) $updateProfile['gender'] = $data['gender'];
            if (isset($data['date_of_birth'])) $updateProfile['date_of_birth'] = $data['date_of_birth'];

            if (!empty($updateProfile)) {
                $profile->update($updateProfile);
                $profile->save();
            }
            $user->save();
            DB::commit();
            // Reload user with relations to return fresh data
            $user->load(['profile', 'reviewApplication']);

            return ServiceReturn::success(
                data: $user,
                message: __("common.success.data_updated")
            );
        } catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            DB::rollBack();
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
     * Update agency profile
     * @param array $data
     * @return ServiceReturn
     */
    public function updateAgencyProfile(array $data): ServiceReturn
    {
        DB::beginTransaction();
        try {
            $user = $this->userRepository->queryUser()
                ->where('id', $data['user_id'])
                ->where('role', UserRole::AGENCY->value)
                ->first();
            if (!$user) {
                throw new ServiceException(__("error.user_not_found"));
            }
            // 1. Update Password if old_pass provided
            if (!empty($data['old_pass'])) {
                if (!Hash::check($data['old_pass'], $user->password)) {
                    // Fix: return ServiceReturn object directly
                    return ServiceReturn::error(__('auth.error.wrong_password'));
                }
                $user->update(['password' => Hash::make($data['new_pass'])]);
            }

            // 2. Update UserReviewApplication
            $reviewApp = $user->getAgencyReviewsAttribute()->first();
            if (!$reviewApp) {
                throw new ServiceException(__("error.user_not_found"));
            }
            $updateData = [];
            if (isset($data['bio'])){
                $updateData['bio'] = Helper::multilingualPayload($data, 'bio');
            }
            if (isset($data['lat'])) $updateData['latitude'] = (float) $data['lat'];
            if (isset($data['lng'])) $updateData['longitude'] = (float) $data['lng'];
            if (isset($data['address'])) $updateData['address'] = $data['address'];
            if (!empty($updateData)) {
                $this->userReviewApplicationRepository->update($reviewApp->id, $updateData);
            }

            // 3. Update UserProfile
            $profile = $user->profile;
            if (!$profile) {
                throw new ServiceException(__("error.user_not_found"));
            }
            $updateProfile = [];
            if (isset($data['gender'])) $updateProfile['gender'] = $data['gender'];
            if (isset($data['date_of_birth'])) $updateProfile['date_of_birth'] = $data['date_of_birth'];

            if (!empty($updateProfile)) {
                $profile->update($updateProfile);
                $profile->save();
            }
            $user->save();
            DB::commit();
            // Reload user with relations to return fresh data
            $user->load(['profile', 'reviewApplication']);

            return ServiceReturn::success(
                data: $user,
                message: __("common.success.data_updated")
            );
        } catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi UserService@updateAgencyProfile",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Link KTV to Referrer via QR
     * @param int $ktvId
     * @param int $referrerId
     * @return ServiceReturn
     */
    public function linkKtvToReferrer(int $ktvId, int $referrerId): ServiceReturn
    {
        try {
            $ktv = $this->userRepository
                ->queryUser()
                ->where('id', $ktvId)
                ->where('role', UserRole::KTV->value)
                ->first();

            // Người giới thiệu phải là KTV hoặc Agency
            $referrer = $this->userRepository
                ->queryUser()
                ->where('id', $referrerId)
                ->whereIn('role', [UserRole::AGENCY->value, UserRole::KTV->value])
                ->first();

            if (!$ktv || !$referrer) {
                throw new ServiceException(__('common_error.data_not_found'));
            }

            // Prevent self-linking
            if ($ktv->id === $referrer->id) {
                throw new ServiceException(__('common_error.cannot_link_self'));
            }

            $reviewApplication = $ktv->reviewApplication;
            // KTV phải có review application
            if (!$reviewApplication) {
                throw new ServiceException(__('common_error.data_not_found'));
            }
            // Nếu KTV đã có referrer_id, không thể thay đổi
            if ($reviewApplication->referrer_id) {
                throw new ServiceException(__('error.cannot_change_referrer'));
            }

            // update review application
            $ktv->reviewApplication()->update([
                'referrer_id' => $referrer->id,
            ]);

            // Phục vụ việc trả tiền giới thiệu cho KTV
            WalletTransactionJob::dispatch(
                data: [
                    'referral_id' => $referrer->id,
                    'user_id' => $ktv->id
                ],
                case: WalletTransCase::REWARD_FOR_KTV_REFERRAL
            );

            return ServiceReturn::success(
                message: __('common.success.data_updated')
            );
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
        catch (\Exception $e) {
            LogHelper::error('Lỗi UserService@linkKtvToReferrer', $e);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Lấy config lịch làm việc của KTV
     * @param int $ktvId
     * @return ServiceReturn
     */
    public function handleGetScheduleKtv(int $ktvId): ServiceReturn
    {
        try {
            $ktv = $this->userRepository->query()->find($ktvId);

            if (!$ktv || $ktv->role !== UserRole::KTV->value) {
                throw new ServiceException(__('common_error.data_not_found'));
            }

            // Lấy config lịch làm việc của KTV
            $schedules = $this->userKtvScheduleRepository->query()
                ->where('ktv_id', $ktvId)
                ->first();
            // Nếu không có config, tạo config mặc định
             if (!$schedules) {
                $schedules = $this->userKtvScheduleRepository->create([
                    'ktv_id' => $ktvId,
                    'working_schedule' => KTVConfigSchedules::getDefaultSchema(),
                    'is_working' => true,
                ]);
            }

            return ServiceReturn::success(
                data: $schedules,
            );
        }catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
        catch (\Exception $e) {
            LogHelper::error('Lỗi UserService@handleGetScheduleKtv', $e);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Cập nhật config lịch làm việc của KTV
     * @param array $data
     * @return ServiceReturn
     */
    public function handleUpdateScheduleKtv(array $data): ServiceReturn
    {
        try {
            $user = Auth::user();
            $resSchedule = $this->handleGetScheduleKtv($user->id);
            if ($resSchedule->isError()) {
                throw new ServiceException($resSchedule->getMessage());
            }
            $schedules = $resSchedule->getData();

            // Xử lý dữ liệu config lịch làm việc
            $workingSchedules = $data['working_schedule'];
            foreach ($workingSchedules as $key => $workingSchedule) {
                $workingSchedules[$key] = [
                    'active' => $workingSchedule['active'],
                    'start_time' => $workingSchedule['start_time'] ?? "08:00",
                    'end_time' => $workingSchedule['end_time'] ?? "16:00",
                    'day_key' => $workingSchedule['day_key'],
                ];
            }
            // Cập nhật config lịch làm việc
            $schedules->update([
                'working_schedule' => $workingSchedules,
                'is_working' => $data['is_working'],
            ]);
            return ServiceReturn::success(
                data: $schedules,
            );
        }catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
        catch (\Exception $e) {
            LogHelper::error('Lỗi UserService@handleUpdateScheduleKtv', $e);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Lấy file hợp đồng
     * @param ContractFileType $type
     * @return ServiceReturn
     */
    public function getContractFile(ContractFileType $type): ServiceReturn
    {
        try {
            $file = $this->staticContractRepository->query()
                ->where('type', $type->value)
                ->first();
            if (!$file) {
                throw new ServiceException(__('common_error.data_not_found'));
            }
            return ServiceReturn::success(
                data: $file,
            );
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $e) {
            LogHelper::error('Lỗi UserService@getContractFile', $e);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Cập nhật trạng thái is_leader cho một KTV cụ thể
     */
    public function updateKtvLeaderStatus(int|string $referrerId): ServiceReturn
    {
        try {
            // Kiểm tra referrer có tồn tại và là KTV không
            $referrer = $this->userRepository->query()
                ->where('id', $referrerId)
                ->where('role', UserRole::KTV->value)
                ->where('is_active', true)
                ->first();

            if (!$referrer) {
                return ServiceReturn::error(__('common_error.data_not_found'));
            }

            // Đếm số KTV đã được duyệt mà KTV này giới thiệu
            $invitedKtvCount = $this->userReviewApplicationRepository->getCountKtvReferrers($referrerId);

            $minReferrals = $this->configService->getKtvLeaderMinReferrals();

            if ($invitedKtvCount < $minReferrals) {
                return ServiceReturn::success(
                    data: ['is_updated' => false, 'reason' => 'not_enough_referrals']
                );
            }

            // Lấy hồ sơ apply KTV của người giới thiệu
            $reviewApplication = $this->userReviewApplicationRepository->query()
                ->where('user_id', $referrerId)
                ->where('role', UserRole::KTV->value)
                ->first();

            if (!$reviewApplication) {
                return ServiceReturn::error(__('common_error.data_not_found'));
            }

            // Đánh dấu là trưởng nhóm KTV
            if (!$reviewApplication->is_leader) {
                $reviewApplication->is_leader = true;
                $reviewApplication->save();
            }

            return ServiceReturn::success(
                data: [
                    'is_updated' => true,
                    'referrer_id' => $referrerId,
                    'invited_count' => $invitedKtvCount,
                ]
            );
        } catch (\Exception $e) {
            LogHelper::error('Lỗi UserService@updateKtvLeaderStatus', $e);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Cập nhật trạng thái is_leader cho tất cả KTV có đủ số lượng giới thiệu
     * @param int $minReferrals Số lượng giới thiệu tối thiểu để lên trưởng nhóm
     * @return ServiceReturn
     */
    public function updateAllKtvLeaderStatus($minReferrals = 10): ServiceReturn
    {
        DB::beginTransaction();
        try {

            // Lấy tất cả KTV có giới thiệu người khác
            $ktvReferrers = $this->userRepository->query()
                ->where('role', UserRole::KTV->value)
                ->where('is_active', true)
                ->whereHas('reviewApplication', function ($query) {
                    $query->where('role', UserRole::KTV->value);
                })
                ->get();

            $updatedCount = 0;
            $skippedCount = 0;


            foreach ($ktvReferrers as $referrer) {
                // Đếm số KTV đã được duyệt mà KTV này giới thiệu
                $invitedKtvCount = $this->userReviewApplicationRepository->getCountKtvReferrers($referrer->id);

                // Lấy hồ sơ apply KTV của người giới thiệu
                $reviewApplication = $this->userReviewApplicationRepository->query()
                    ->where('user_id', $referrer->id)
                    ->where('role', UserRole::KTV->value)
                    ->first();

                if (!$reviewApplication) {
                    $skippedCount++;
                    continue;
                }

                // Kiểm tra điều kiện và cập nhật
                if ($invitedKtvCount >= $minReferrals) {
                    if (!$reviewApplication->is_leader) {
                        $reviewApplication->is_leader = true;
                        $reviewApplication->save();
                        $updatedCount++;
                    }
                }
            }

            DB::commit();

            return ServiceReturn::success(
                data: [
                    'updated_count' => $updatedCount,
                    'skipped_count' => $skippedCount,
                    'total_checked' => $ktvReferrers->count(),
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::error('Lỗi UserService@updateAllKtvLeaderStatus', $e);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

}
