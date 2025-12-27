<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\DirectFile;
use App\Enums\Language;
use App\Enums\ServiceDuration;
use App\Models\Coupon;
use App\Models\User;
use App\Repositories\BookingRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CouponRepository;
use App\Repositories\CouponUserRepository;
use App\Repositories\ServiceRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServiceService extends BaseService
{
    public function __construct(
        protected ServiceRepository  $serviceRepository,
        protected CategoryRepository $categoryRepository,
        protected CouponRepository   $couponRepository,
        protected BookingRepository  $bookingRepository,
        protected CouponUserRepository $couponUserRepository,
        protected CouponService $couponService,
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
     * Lấy tất cả categories (không phân trang)
     * @return ServiceReturn
     */
    public function allCategories(): ServiceReturn
    {
        try {
            $categories = $this->categoryRepository->queryCategory()->get();
            return ServiceReturn::success(
                data: $categories
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi ServiceService@allCategories",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
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
                    message: __("error.service_not_found")
                );
            }
            if (!$service->is_active) {
                throw new ServiceException(
                    message: __("error.service_not_active")
                );
            }
            return ServiceReturn::success(
                data: $service
            );
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
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
            $query = $this->couponRepository->queryCoupon();
            $query = $this->couponRepository->filterQuery($query, $dto->filters);
            $query = $this->couponRepository->sortQuery($query, $dto->sortBy, $dto->direction);
            $coupons = $query->get();
            // 3. Lọc lượt nhặt trong ngày (Daily Limit)
            $validCoupons = $coupons->filter(function ($coupon) {
                // Nếu CHƯA sở hữu: Kiểm tra xem hôm nay còn lượt nhặt không
                $valid = $this->couponService->validateCollectCoupon($coupon);
                return $valid->isSuccess();
            });

            return ServiceReturn::success(
                data: $validCoupons->values()
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


    /**
     * Tạo dịch vụ mới
     * @param array $data
     * @return ServiceReturn
     */
    public function createService(array $data): ServiceReturn
    {
        // Biến lưu đường dẫn ảnh để cleanup nếu lỗi
        $uploadedPath = null;
        DB::beginTransaction();
        try {
            $image = $data['image'];
            if (!($image instanceof \Illuminate\Http\UploadedFile)) {
                throw new ServiceException(
                    message: __("error.invalid_image")
                );
            }
            $uploadedPath = $image->store(DirectFile::makePathById(
                type: DirectFile::SERVICE,
                id: $data['user_id']
            ), 'public');

            $name = Helper::multilingualPayload($data, 'name');
            $description = Helper::multilingualPayload($data, 'description');

            $service = $this->serviceRepository->create([
                'name' => $name,
                'description' => $description,
                'category_id' => $data['category_id'],
                'image_url' => $uploadedPath,
                'is_active' => $data['is_active'],
                'user_id' => $data['user_id'],
            ]);
            $service->options()->createMany($data['options']);

            DB::commit();
            return ServiceReturn::success(
                data: $service
            );
        } catch (ServiceException $exception) {
            DB::rollBack();
            if ($uploadedPath && Storage::disk('public')->exists($uploadedPath)) {
                Storage::disk('public')->delete($uploadedPath);
            }
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            if ($uploadedPath && Storage::disk('public')->exists($uploadedPath)) {
                Storage::disk('public')->delete($uploadedPath);
            }
            LogHelper::error(
                message: "Lỗi ServiceService@createService",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /*
     * Cập nhật dịch vụ
     * @param string $id
     * @param array $data
     * @return ServiceReturn
     */
    public function updateService(string $id, array $data): ServiceReturn
    {
        $uploadedPath = null; // fallback nếu xảy ra lỗi
        DB::beginTransaction();
        try {
            $service = $this->serviceRepository->query()->find($id);
            if (!$service) {
                return ServiceReturn::error(
                    message: __("error.service_not_found")
                );
            }
            if ($service->user_id !== $data['user_id']) {
                return ServiceReturn::error(
                    message: __("error.service_not_authorized")
                );
            }

            if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                if ($service->image_url && Storage::disk('public')->exists($service->image_url)) {
                    Storage::disk('public')->delete($service->image_url);
                }
                $uploadedPath = $data['image']->store(DirectFile::makePathById(
                    type: DirectFile::SERVICE,
                    id: $service->user_id
                ), 'public');
                $data['image_url'] = $uploadedPath;
            }

            if (isset($data['name'])) {
                $data['name'] = Helper::multilingualPayload($data, 'name');
            }
            if (isset($data['description'])) {
                $data['description'] = Helper::multilingualPayload($data, 'description');
            }

            $service->update($data);

            if (isset($data['options'])) {
                $service->options()->delete();
                $service->options()->createMany($data['options']);
            }

            DB::commit();
            return ServiceReturn::success(
                data: $service
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            if ($uploadedPath && Storage::disk('public')->exists($uploadedPath)) {
                Storage::disk('public')->delete($uploadedPath);
            }
            LogHelper::error(
                message: "Lỗi ServiceService@updateService",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Xóa dịch vụ
     * @param int $id
     * @return ServiceReturn
     */
    public function deleteService(int $id): ServiceReturn
    {
        try {
            $service = $this->serviceRepository->query()->find($id);
            if (!$service) {
                return ServiceReturn::error(
                    message: __("error.service_not_found")
                );
            }
            $user = Auth::user();
            if ($service->user_id !== $user->id) {
                return ServiceReturn::error(
                    message: __("error.service_not_authorized")
                );
            }

            $haveAnyBooking = $this->bookingRepository->query()->where('service_id', $id)->exists();
            if ($haveAnyBooking) {
                return ServiceReturn::error(
                    message: __("error.service_have_booking")
                );
            }
            $service->delete();
            return ServiceReturn::success(
                message: __("common.success.data_deleted")
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi ServiceService@deleteService",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }
        /**
         * Lấy thông tin chi tiết dịch vụ
         * @param string $id
         * @return ServiceReturn
         */
    public function detailService(string $id): ServiceReturn
    {
        try {
            $service = $this->serviceRepository->query()->find($id);
            if (!$service) {
                return ServiceReturn::error(
                    message: __("error.service_not_found")
                );
            }
            $user = Auth::user();
            if ($service->user_id !== $user->id) {
                return ServiceReturn::error(
                    message: __("error.service_not_authorized")
                );
            }

            return ServiceReturn::success(
                data: $service
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi ServiceService@detailService",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("error.service_not_authorized")
            );
        }

    }
}
