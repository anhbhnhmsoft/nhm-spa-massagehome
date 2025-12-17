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
use App\Models\User;
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
    ) {
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
     * Lấy mã người dùng sở hữu và mã hợp lệ thời điểm booking
     * @param FilterDTO $dto
     * @return ServiceReturn
     */

    public function getListCoupon(FilterDTO $dto): ServiceReturn
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $now = Carbon::now();
            $currentTime = $now->format('H:i');
            $today = $now->format('Y-m-d');

            // 1. Lấy danh sách Coupon ID người dùng đang sở hữu và chưa dùng
            $ownedCouponIds = $user->collectionCoupons()
                ->where('is_used', false)
                ->pluck('coupon_id')
                ->toArray();

            // 2. Query tất cả coupon hợp lệ tại thời điểm booking
            $query = $this->couponRepository->queryCoupon();
            $query = $this->couponRepository->filterQuery($query, $dto->filters);
            $query = $this->couponRepository->sortQuery($query, $dto->sortBy, $dto->direction);

            $coupons = $query->get();
            // 3. Xử lý Time Slots & Daily Limits
            $filteredCoupons = $coupons->filter(function ($coupon) use ($ownedCouponIds, $currentTime, $today) {
                $config = $coupon->config ?? [];
                $isOwned = in_array($coupon->id, $ownedCouponIds);

                // A. Kiểm tra Khung giờ(allowed_time_slots)
                $slots = $config['allowed_time_slots'] ?? [];
                if (!empty($slots)) {
                    $inSlot = false;
                    foreach ($slots as $slot) {
                        if ($currentTime >= ($slot['start'] ?? '00:00') && $currentTime <= ($slot['end'] ?? '23:59')) {
                            $inSlot = true;
                            break;
                        }
                    }
                    if (!$inSlot) return false; // Không nằm trong khung giờ cho phép sử dụng/hiển thị
                }

                // B. Kiểm tra giới hạn thu thập theo ngày (Nếu chưa sở hữu)
                if (!$isOwned) {
                    $collectLimit = $config['per_day_global'] ?? 0; // Giới hạn thu thập/ngày
                    $currentCollected = $config['daily_collected'][$today] ?? 0;

                    if ($collectLimit > 0 && $currentCollected >= $collectLimit) {
                        return false; // Hết lượt thu thập hôm nay
                    }
                }

                return true;
            });

            return ServiceReturn::success(
                data: $filteredCoupons->values()
            );
        } catch (\Exception $exception) {
            LogHelper::error("Lỗi ServiceService@getListCoupon", $exception);
            return ServiceReturn::error(message: __("common_error.server_error"));
        }
    }

    /**
     * Lấy danh sách khách hàng đã đặt lịch trong ngày hôm nay
     * @param string $ktvUserId
     * @return ServiceReturn
     */
    public function getTodayBookedCustomers(
        string $ktvUserId
    ): ServiceReturn {
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
