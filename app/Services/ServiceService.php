<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\BookingRepository;
use App\Repositories\CategoryPriceRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CouponRepository;
use App\Repositories\CouponUserRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ServiceService extends BaseService
{
    public function __construct(
        protected CategoryRepository      $categoryRepository,
        protected CouponRepository        $couponRepository,
        protected BookingRepository       $bookingRepository,
        protected CouponUserRepository    $couponUserRepository,
        protected CouponService           $couponService,
        protected CategoryPriceRepository $categoryPriceRepository,
        protected UserRepository          $userRepository,
        protected ServiceRepository       $serviceRepository,
    )
    {
        parent::__construct();
    }

    /**
     * Lấy danh sách danh mục dịch vụ
     * @param FilterDTO $dto
     * @return ServiceReturn
     */
    public function categoryPaginate(FilterDTO $dto): ServiceReturn
    {
        try {
            $query = $this->categoryRepository->queryCategory();
            $query = $this->categoryRepository->filterQuery(
                query: $query,
                filters: $dto->filters
            );
            $query = $this->categoryRepository->sortQuery(
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
     * Lấy tất cả categories dành cho KTV (không phân trang)
     * @return ServiceReturn
     */
    public function allCategories(): ServiceReturn
    {
        return $this->execute(function () {
            $user = Auth::user();
            $categories = $this->categoryRepository
                ->query()
                ->with(['prices'])
                ->withCount(['bookings' => function ($query) use ($user) {
                    $query->where('ktv_user_id', $user->id);
                }])
                ->withExists(['users as is_registered' => function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                }])
                ->get();
            return ServiceReturn::success(
                data: $categories
            );
        });
    }

    /**
     * Lấy mã người dùng sở hữu và mã hợp lệ thời điểm booking
     * @param FilterDTO $dto
     * @return ServiceReturn
     */
    public function getListCoupon(FilterDTO $dto): ServiceReturn
    {
        try {
            $query = $this->couponRepository->queryCoupon();
            $query = $this->couponRepository->filterQuery($query, $dto->filters);
            $query = $this->couponRepository->sortQuery($query, $dto->sortBy, $dto->direction);
            $coupons = $query->get();


            return ServiceReturn::success(
                data: $coupons
            );
        } catch (\Exception $exception) {
            LogHelper::error("Lỗi ServiceService@getListCoupon", $exception);
            return ServiceReturn::error(message: __("common_error.server_error"));
        }
    }

    /**
     * Lấy danh sách mã coupon đã được sử dụng
     * @param FilterDTO $dto
     * @return ServiceReturn
     */
    public function couponUserPaginate(FilterDTO $dto): ServiceReturn
    {
        try {
            $query = $this->couponUserRepository->queryCouponUser();
            $query = $this->couponUserRepository->filterQuery($query, $dto->filters);
            $query = $this->couponUserRepository->sortQuery($query, $dto->sortBy, $dto->direction);
            $paginate = $query->paginate(
                perPage: $dto->perPage,
                page: $dto->page
            );
            return ServiceReturn::success(
                data: $paginate
            );
        } catch (\Exception $exception) {
            LogHelper::error("Lỗi ServiceService@couponUserPaginate", $exception);
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
     * Cập nhật dịch vụ cho KTV
     * @param $categoryId
     * @param $userId
     */
    public function setService($categoryId, $userId): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($categoryId, $userId) {

                $category = $this->categoryRepository
                    ->query()
                    ->find($categoryId);

                if (!$category) {
                    throw new ServiceException(__("error.service_not_found"));
                }
                /**
                 * @var User $user
                 */
                $user = $this->userRepository
                    ->queryUser()
                    ->where('role', UserRole::KTV->value)
                    ->where('id', $userId)
                    ->first();
                if (!$user) {
                    throw new ServiceException(__("error.user_not_found"));
                }

                // Cập nhật dịch vụ cho KTV
                $user->categories()->toggle($categoryId);
            },
            useTransaction: true
        );

    }

    /**
     * Cập nhật số lượng dịch vụ đã thực hiện (buff ảo)
     * @param array $data
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function updateServicePerformedCount(array $data): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($data) {
                foreach ($data as $service) {
                    $this->serviceRepository->update($service['id'],[
                            'performed_count' => (int)$service['performed_count'],
                    ]);
                }
            },
            useTransaction: true
        );
    }
}
