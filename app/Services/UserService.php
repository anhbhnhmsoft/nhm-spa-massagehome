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
use App\Enums\Language;
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
use App\Repositories\CategoryRepository;
use App\Repositories\CouponUserRepository;
use App\Repositories\DangerSupportRepository;
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
use Filament\Notifications\Notification;
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
        protected DangerSupportRepository         $dangerSupportRepository,
        protected CategoryRepository              $categoryRepository,
    )
    {
        parent::__construct();
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
        return $this->execute(
            callback: function () use ($id) {
                // Lấy config khoảng thời gian nghỉ giữa 2 buổi
                $breakTimeGap = $this->configService->getConfigValue(ConfigName::BREAK_TIME_GAP);
                // Lấy config giá di chuyển / km
                $priceTransportation = $this->configService->getConfigValue(ConfigName::PRICE_TRANSPORTATION);

                // Lấy thông tin KTV
                $ktv = $this->userRepository->queryKTV()
                    ->with([
                        'gallery',
                        // Chỉ lấy 1 review
                        'reviewsReceived' => function ($query) {
                            $query->where('hidden', false)
                                ->latest('created_at')
                                ->limit(1);
                        },
                        'ktvBookings' => function ($query) {
                            $query->where('status', BookingStatus::ONGOING->value)
                                ->orderBy('start_time', 'asc')
                                ->limit(1);
                        },
                        'categories',
                        'categories.prices',
                    ])
                    ->find($id);
                if (!$ktv) {
                    throw new ServiceException(
                        message: __("common_error.data_not_found")
                    );
                }
                return [
                    'ktv' => $ktv,
                    'break_time_gap' => $breakTimeGap,
                    'price_transportation' => $priceTransportation,
                ];
            }
        );
    }

    /**
     * Duyệt hồ sơ KTV
     * @param int $id
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function activeStaffApply(int $id): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($id) {
                $user = $this->userRepository->query()
                    ->where('id', $id)
                    ->first();
                if (!$user) {
                    throw new ServiceException(
                        message: __("common_error.data_not_found")
                    );
                }
                $user->is_active = true;
                $apply = $user->reviewApplication;
                if (!$apply) {
                    throw new ServiceException(
                        message: __("common_error.data_not_found")
                    );
                }
                $apply->status = ReviewApplicationStatus::APPROVED;
                $apply->effective_date = now();
                $user->role = $apply->role;
                $apply->save();
                $user->save();

                // Tạo lịch làm việc mặc định cho KTV
                if ($apply->role === UserRole::KTV->value) {
                    $user->schedule()->create([
                        'is_working' => true,
                        'working_schedule' => KTVConfigSchedules::getDefaultSchema(),
                    ]);
                    // Đăng ký hết tất cả các dịch vụ cho KTV
                    $allCategoryIds = $this->categoryRepository->query()
                        ->pluck('id')
                        ->toArray();
                    $user->categories()->sync($allCategoryIds);
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
            },
            useTransaction: true
        );
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
            } else {
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
        } catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
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
     * Đăng ký làm KTV
     * @param array $data - dựa theo ApplyTechnicalRequest
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function applyTechnical(array $data): ServiceReturn
    {
        $tempFiles = []; // Chứa các file MỚI vừa upload để xóa nếu lỗi
        $filesToDeleteOnSuccess = []; // Chứa các file CŨ cần xóa nếu commit thành công

        return $this->execute(
            callback: function () use ($data, &$filesToDeleteOnSuccess, &$tempFiles) {
                $user = Auth::user();
                $profile = $this->userProfileRepository->query()
                    ->where('user_id', $user->id)
                    ->first();
                if (!$profile) {
                    throw new ServiceException(message: __("common_error.data_not_found"));
                }

                // Kiểm tra trạng thái review application
                $filesToDeleteOnSuccess = $this->checkExistReviewApplication($user->id);

                // Kiểm tra referrer_id
                if (!empty($data['referrer_id'])) {
                    $agency = $this->userRepository->queryUser()
                        ->where('id', $data['referrer_id'])
                        ->whereIn('role', [UserRole::AGENCY->value, UserRole::KTV->value])
                        ->first();
                    if (!$agency) {
                        throw new ServiceException(message: __("error.referrer_not_found"));
                    }
                }

                // Chuẩn bị dữ liệu và Lưu review application mới
                $this->userReviewApplicationRepository->create([
                    'user_id' => $user->id,
                    'status' => ReviewApplicationStatus::PENDING->value,
                    'role' => UserRole::KTV->value,
                    'referrer_id' => $data['referrer_id'] ?? null,
                    'is_leader' => $data['is_leader'] ?? false,
                    'nickname' => $data['nickname'],
                    'experience' => $data['experience'],
                    'application_date' => now(),
                    'bio' => [
                        Language::VIETNAMESE->value => $data['bio'],
                        Language::ENGLISH->value => $data['bio'],
                        Language::CHINESE->value => $data['bio'],
                    ],
                ]);

                // Upload files review application
                $tempFiles = $this->uploadFilesReviewApplication($data['file_uploads'], $user->id, UserRole::KTV);

                // Upload profile avatar
                if (!$data['avatar'] instanceof UploadedFile) {
                    throw new ServiceException(message: __("common_error.invalid_parameter"));
                }
                $path = $data['avatar']->store(DirectFile::makePathById(
                    type: DirectFile::AVATAR_USER,
                    id: $user->id
                ), 'public');

                $tempFiles[] = ['disk' => 'public', 'path' => $path];
                // Update Profile
                $this->userProfileRepository->update($profile->user_id, [
                    'date_of_birth' => Carbon::make($data['dob'])->format('Y-m-d'),
                    'avatar_url' => $path,
                ]);

                return ServiceReturn::success();
            },
            useTransaction: true,
            catchCallback: function () use ($tempFiles) {
                Helper::cleanupFiles($tempFiles); // Xóa file mới vừa upload lỗi
            },
            afterCommitCallback: function () use ($filesToDeleteOnSuccess) {
                Helper::cleanupFiles($filesToDeleteOnSuccess);
            }
        );
    }


    /**
     * Đăng ký làm KTV
     * @param array $data - dựa theo ApplyAgencyRequest
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function applyAgency(array $data): ServiceReturn
    {
        $tempFiles = []; // Chứa các file MỚI vừa upload để xóa nếu lỗi
        $filesToDeleteOnSuccess = []; // Chứa các file CŨ cần xóa nếu commit thành công

        return $this->execute(
            callback: function () use ($data, &$filesToDeleteOnSuccess, &$tempFiles) {
                $user = Auth::user();
                // Kiểm tra trạng thái review application
                $filesToDeleteOnSuccess = $this->checkExistReviewApplication($user->id);

                // Chuẩn bị dữ liệu và Lưu review application mới
                $this->userReviewApplicationRepository->create([
                    'user_id' => $user->id,
                    'status' => ReviewApplicationStatus::PENDING->value,
                    'role' => UserRole::AGENCY->value,
                    'nickname' => $data['nickname'],
                    'address' => $data['address'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'application_date' => now(),
                ]);

                // Upload files review application
                $tempFiles = $this->uploadFilesReviewApplication($data['file_uploads'], $user->id, UserRole::AGENCY);

                return ServiceReturn::success();
            },
            useTransaction: true,
            catchCallback: function () use ($tempFiles) {
                Helper::cleanupFiles($tempFiles); // Xóa file mới vừa upload lỗi
            },
            afterCommitCallback: function () use ($filesToDeleteOnSuccess) {
                Helper::cleanupFiles($filesToDeleteOnSuccess);
            }
        );
    }


    /**
     * Kiểm tra người dùng hiện tại đã nộp đơn ứng tuyển chưa ?
     * @return ServiceReturn
     */
    public function checkApplyPartnerForCurrentUser(): ServiceReturn
    {
        $user = Auth::user();
        try {
            $checkApply = true;
            $reviewApplication = $this->userReviewApplicationRepository->query()
                ->where('user_id', $user->id)
                ->first();
            if ($reviewApplication) {
                $checkApply = $reviewApplication->status == ReviewApplicationStatus::REJECTED;
            }
            return ServiceReturn::success(
                data: [
                    'check_apply' => $checkApply,
                    'review_application' => $reviewApplication
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
            $preparedData = [
                'user_id' => $user->id,
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'desc' => $data['desc'] ?? '',
                'is_primary' => false
            ];
            $userAddress = $this->userAddressRepository->create($preparedData);
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
                ->where('is_primary', false)
                ->first();

            if (!$userAddress) {
                throw new ServiceException(__("error.address_not_found"));
            }
            $preparedData = [
                'address' => $data['address'] ?? $userAddress->address,
                'latitude' => $data['latitude'] ?? $userAddress->latitude,
                'longitude' => $data['longitude'] ?? $userAddress->longitude,
                'desc' => $data['desc'] ?? $userAddress->desc,
            ];

            $userAddress->update($preparedData);

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
            // Kiểm tra nếu địa chỉ xóa là chính thì không thể xóa
            if ($userAddress->is_primary) {
                throw new ServiceException(__("error.cant_delete_primary_address"));
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
     * Set địa chỉ mặc định cho user (địa chỉ cập nhật location chính xác GPS)
     * @param $userId
     * @param float $latitude
     * @param float $longitude
     * @param string $address
     * @return ServiceReturn
     */
    public function setDefaultAddress(
        $userId,
        float $latitude,
        float $longitude,
        string $address,
    ): ServiceReturn
    {
        try {
            $this->userAddressRepository->query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'is_primary' => true,
                ],
                [
                    'address' => $address,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]
            );
            return ServiceReturn::success();
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi UserService@setDefaultAddress",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
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
            if (isset($data['bio'])) {
                $updateData['bio'] = Helper::multilingualPayload($data, 'bio');
            }
            if (isset($data['experience'])) $updateData['experience'] = $data['experience'];
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
            if (isset($data['bio'])) {
                $updateData['bio'] = Helper::multilingualPayload($data, 'bio');
            }
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
        } catch (\Exception $e) {
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
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $e) {
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
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $e) {
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

    /**
     * Gửi hỗ trợ nguy hiểm
     * @param array $data
     * @return ServiceReturn
     */
    public function handleSendDangerSupport(array $data): ServiceReturn
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $payload = [
                'user_id' => $user->id,
                'content' => $data['message'] ?? "",
                'status' => \App\Enums\DangerSupportStatus::PENDING,
            ];

            // 1. Logic location Default
            if (isset($data['lat']) && isset($data['lng'])) {
                $payload['latitude'] = $data['lat'];
                $payload['longitude'] = $data['lng'];
                if (isset($data['address'])) {
                    $payload['address'] = $data['address'];
                }
            } else {
                $primaryAddress = $user->primaryAddress;
                if ($primaryAddress) {
                    $payload['latitude'] = $primaryAddress->latitude;
                    $payload['longitude'] = $primaryAddress->longitude;
                    $payload['address'] = $primaryAddress->address;
                }
            }

            $nearestBooking = $this->bookingRepository->query()
                ->where('ktv_user_id', $user->id)
                ->orWhere('user_id', $user->id)
                ->where('status', BookingStatus::ONGOING)
                ->orderBy('start_time', 'desc')
                ->first();

            if ($nearestBooking) {
                $payload['booking_id'] = null;
            }

            $dangerSupport = $this->dangerSupportRepository->create($payload);

            $this->notificationService->sendAdminNotification(NotificationAdminType::EMERGENCY_SUPPORT, [
                'danger_support_id' => $dangerSupport->id,
                'booking_id' => $nearestBooking?->id,
            ]);

            DB::commit();

            return ServiceReturn::success();

        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::error('Lỗi UserService@handleSendDangerSupport', $e);
            return ServiceReturn::error(__('common_error.server_error'));
        }
    }


    /**
     *  ---- Protected methods ----
     */

    /**
     * Kiểm tra xem người dùng có tồn tại review application nào không
     * @param int $userId
     * @return array<array{disk: string, path: string}> - Mảng chứa thông tin các file cần xóa khi thành công
     * @throws ServiceException
     */
    protected function checkExistReviewApplication(int $userId)
    {
        $filesToDeleteOnSuccess = [];
        // 1. Kiểm tra trạng thái review application
        $existingReview = $this->userReviewApplicationRepository->query()
            ->where('user_id', $userId)
            ->first();

        if ($existingReview) {
            if ($existingReview->status == ReviewApplicationStatus::PENDING) {
                throw new ServiceException(message: __("error.user_have_review_application_pending"));
            } else if ($existingReview->status == ReviewApplicationStatus::APPROVED) {
                throw new ServiceException(message: __("error.user_have_review_application"));
            } else if ($existingReview->status == ReviewApplicationStatus::REJECTED) {
                // Lấy danh sách file CŨ để chuẩn bị xóa vật lý SAU KHI commit
                $oldFiles = $this->userFileRepository->query()
                    ->where('user_id', $userId)
                    ->whereIn('type', [
                        UserFileType::IDENTITY_CARD_FRONT->value,
                        UserFileType::IDENTITY_CARD_BACK->value,
                        UserFileType::KTV_IMAGE_DISPLAY->value,
                        UserFileType::LICENSE->value,
                        UserFileType::FACE_WITH_IDENTITY_CARD->value,
                    ])
                    ->get();

                foreach ($oldFiles as $file) {
                    $filesToDeleteOnSuccess[] = [
                        'disk' => $file->is_public ? 'public' : 'private',
                        'path' => $file->file_path,
                    ];
                    // Chỉ xóa bản ghi trong DB tại đây
                    $this->userFileRepository->delete($file->id);
                }

                // Xóa review application cũ trong DB
                $this->userReviewApplicationRepository->delete($existingReview->id);
            }
        }

        return $filesToDeleteOnSuccess;
    }

    /**
     * Upload files review application
     * @param array $files
     * @param int $userId
     * @param UserRole $role
     * @return array
     * @throws ServiceException
     */
    protected function uploadFilesReviewApplication(array $files, int $userId, UserRole $role): array
    {
        $tempFiles = [];
        foreach ($files as $fileUpload) {
            $typeUpload = $fileUpload['type_upload'];
            $file = $fileUpload['file'];
            if (!$file instanceof UploadedFile) {
                throw new ServiceException(message: __("common_error.invalid_parameter"));
            }
            $isPublic = !in_array($typeUpload, UserFileType::getTypeUploadToPrivateDisk());
            $disk = $isPublic ? 'public' : 'private';
            $path = $file->store(DirectFile::makePathById(
                type: DirectFile::USER_FILE_UPLOAD,
                id: $userId
            ), $disk);
            // Lưu vào mảng temp để xóa nếu Rollback
            $tempFiles[] = ['disk' => $disk, 'path' => $path];
            $this->userFileRepository->create([
                'user_id' => $userId,
                'type' => $typeUpload,
                'file_path' => $path,
                'is_public' => $isPublic,
                'role' => $role->value,
            ]);
        }
        return $tempFiles;
    }

}
