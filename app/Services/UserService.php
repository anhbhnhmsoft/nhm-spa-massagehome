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
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Repositories\UserAddressRepository;
use App\Repositories\UserFileRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserReviewApplicationRepository;
use App\Repositories\WalletRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserService extends BaseService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected UserFileRepository $userFileRepository,
        protected UserReviewApplicationRepository $userReviewApplicationRepository,
        protected UserProfileRepository $userProfileRepository,
        protected WalletRepository $walletRepository,
        protected UserAddressRepository $userAddressRepository
    ) {
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
        try {
            $ktv = $this->userRepository->queryKTV()->find($id);
            if (!$ktv) {
                throw new ServiceException(
                    message: __("common_error.data_not_found")
                );
            }
            return ServiceReturn::success(
                data: $ktv
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi UserService@getKtvById",
                ex: $exception
            );
            return ServiceReturn::error(
                message: "Không tìm thấy KTV"
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
                'referral_code' => Helper::generateReferCodeUser(UserRole::KTV)
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

    public function activeKTVapply(int $id)
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
            $user->save();

            if ($user->reviewApplication) {
                $user->reviewApplication->status = ReviewApplicationStatus::APPROVED;
                $user->reviewApplication->effective_date = now();
                $user->reviewApplication->save();
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
            return ServiceReturn::success(
                message: __("common_success.data_updated")
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
                'referral_code' => Helper::generateReferCodeUser(UserRole::AGENCY)
            ]);

            $userReviewApplication = $this->userReviewApplicationRepository->create([
                'user_id' => $userInitial->id,
                'status' => ReviewApplicationStatus::PENDING->value,
                'province_code' => optional($data['reviewApplication'])['province']['name'] ?? null,
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
                message: __("common_success.data_created")
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
            if (isset($data['referral_code']) && $data['referral_code']) {
                $dataUpdate['referral_code'] = $data['referral_code'];
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
                message: __("common_success.data_updated")
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
     * @param array $data
     * @return ServiceReturn
     */
    public function saveAddress(array $data): ServiceReturn
    {

        try {
            DB::beginTransaction();

            $checkAdress = $this->userAddressRepository->query()
                ->where('user_id', $data['user_id'])
                ->where('latitude', $data['latitude'])
                ->where('longitude', $data['longitude'])
                ->first();
            if ($checkAdress) {
                return ServiceReturn::error(
                    message: __("common_error.address_exists")
                );
            }

            $preparedData = [
                'user_id' => $data['user_id'],
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'desc' => $data['desc'] ?? '',
                'is_primary' => $data['is_primary'] ?? false
            ];
            $userAddress = $this->userAddressRepository->create($preparedData);
            DB::commit();
            return ServiceReturn::success(
                data: $userAddress,
                message: __("common_success.data_created")
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
}
