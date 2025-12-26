<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
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

            $multilingualPayload = function ($field) use ($data) {
                $source = $data[$field] ?? [];
                // Tìm giá trị fallback (lấy giá trị đầu tiên không rỗng trong mảng)
                $fallback = null;
                foreach ($source as $val) {
                    if (!empty($val)) {
                        $fallback = $val;
                        break;
                    }
                }
                return [
                    Language::VIETNAMESE->value => !empty($source[Language::VIETNAMESE->value]) ? $source[Language::VIETNAMESE->value] : $fallback,
                    Language::ENGLISH->value    => !empty($source[Language::ENGLISH->value]) ? $source[Language::ENGLISH->value] : $fallback,
                    Language::CHINESE->value    => !empty($source[Language::CHINESE->value]) ? $source[Language::CHINESE->value] : $fallback,
                ];
            };
            $name = $multilingualPayload('name');
            $description = $multilingualPayload('description');

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




}
