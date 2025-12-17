<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\ServiceDuration;
use App\Models\Coupon;
use App\Repositories\BookingRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CouponRepository;
use App\Repositories\ServiceRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceService extends BaseService
{
    public function __construct(
        protected ServiceRepository  $serviceRepository,
        protected CategoryRepository $categoryRepository,
        protected CouponRepository   $couponRepository,
        protected BookingRepository  $bookingRepository,
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
     * Lấy danh sách dịch vụ
     * @param FilterDTO $dto
     * @return ServiceReturn
     */
    public function servicePaginate(FilterDTO $dto): ServiceReturn
    {
        try {
            $query = $this->serviceRepository->queryService();
            $query = $this->serviceRepository->filterQuery(
                query: $query,
                filters: $dto->filters
            );
            $query = $this->serviceRepository->sortQuery(
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
                message: "Lỗi ServiceService@servicePaginate",
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
     * Lấy chi tiết dịch vụ
     * @param int $id
     * @return ServiceReturn
     */
    public function getDetailService(int $id): ServiceReturn
    {
        try {
            $service = $this->serviceRepository->queryService()->find($id);
            if (!$service) {
                throw new ServiceException(
                    message: __("messages.service_not_found")
                );
            }
            if (!$service->is_active) {
                throw new ServiceException(
                    message: __("messages.service_not_active")
                );
            }
            return ServiceReturn::success(
                data: $service
            );
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
        catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi ServiceService@getDetailService",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }


    /**
     * Lấy danh sách mã giảm giá
     * @param FilterDTO $dto
     * @return ServiceReturn
     */
    public function getListCoupon(FilterDTO $dto): ServiceReturn
    {
        try {
            $query = $this->couponRepository->queryCoupon();
            $query = $this->couponRepository->filterQuery(
                query: $query,
                filters: $dto->filters
            );
            $query = $this->couponRepository->sortQuery(
                query: $query,
                sortBy: $dto->sortBy,
                direction: $dto->direction
            );
            $coupons = $query->get();
            return ServiceReturn::success(
                data: $coupons
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi ServiceService@getListCoupon",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Lấy danh sách khách hàng đã đặt lịch trong ngày hôm nay
     * @param string $ktvUserId
     * @return ServiceReturn
     */
    public function getTodayBookedCustomers(
        string $ktvUserId
    ): ServiceReturn
    {
        try {
            $now = \Carbon\Carbon::now();
            $startOfDay = $now->copy()->startOfDay();
            $endOfDay   = $now->copy()->endOfDay();

            $customers = $this->bookingRepository->query()
                ->with([
                    'user.profile',
                ])
                ->whereIn('status', [
                    \App\Enums\BookingStatus::CONFIRMED->value,
                    \App\Enums\BookingStatus::ONGOING->value,
                    \App\Enums\BookingStatus::COMPLETED->value,
                ])
                ->whereBetween('booking_time', [$startOfDay, $endOfDay])
                ->orderBy('booking_time', 'asc')
                ->get();

            return ServiceReturn::success(
                data: $customers
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi UserService@getTodayBookedCustomers",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

}
