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
use App\Repositories\CouponRepository;
use App\Repositories\ServiceRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingService extends BaseService
{

    public function __construct(
        protected BookingRepository $bookingRepository,
        protected ServiceRepository $serviceRepository,
        protected CouponRepository $couponRepository,
    )
    {
        parent::__construct();
    }


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
     * Đặt lịch hẹn dịch vụ
     * @param int $serviceId
     * @param ServiceDuration $duration
     * @param string $address
     * @param string $lat
     * @param string $bookTime
     * @param string $lng
     * @param string|null $note
     * @param int|null $couponId
     * @return ServiceReturn
     */
    public function bookService(
        int             $serviceId,
        ServiceDuration $duration,
        string          $address,
        string          $lat,
        string          $bookTime,
        string          $lng,
        ?string         $note = null,
        ?int            $couponId = null,
    ): ServiceReturn
    {

        DB::beginTransaction();
        try {
            // Lấy thông tin người đặt dịch vụ - là người đang đăng nhập
            $bookBy = Auth::user();
            $bookingTime = Carbon::parse($bookTime); // Start time
            // kiểm tra xem thời gian có hợp lệ ko
            // Kiểm tra xem thời gian đặt có hợp lệ không
            if ($bookingTime->isBefore(now()->addHour())) {
                throw new ServiceException(
                    message: __("validation.book_time.after")
                );
            }

            // Lấy thông tin dịch vụ
            $service = $this->serviceRepository->query()->find($serviceId);
            if (!$service) {
                throw new ServiceException(
                    message: __("error.service_not_found")
                );
            }
            // Kiểm tra xem dịch vụ có sẵn trong khoảng thời gian này không
            $serviceOption = $service->options()
                ->where('duration', $duration->value)
                ->first();
            if (!$serviceOption) {
                throw new ServiceException(
                    message: __("error.service_duration_not_available")
                );
            }
            // Kiểm tra mã giảm giá (nếu có)
            if (!empty($couponId)) {
                $coupon = $this->couponRepository->filterQuery(
                    query: $this->couponRepository->queryCoupon(),
                    filters: [
                        'id' => $couponId,
                        'is_valid' => true,
                        'for_service_id' => $service->id,
                        'get_all' => true,
                    ]
                )->first();
                // Kiểm tra xem mã giảm giá có hợp lệ không
                if (!$coupon) {
                    throw new ServiceException(
                        message: __("error.coupon_invalid")
                    );
                }
                // Tính toán số tiền được giảm
                $discount = $this->calculateDiscount(
                    basePrice: $serviceOption->price,
                    coupon: $coupon
                );
                $finalPrice = max(0, $serviceOption->price - $discount);
                // TODO: Tăng biến đếm used_count của coupon lên 1
                $coupon->increment('used_count');
                $coupon->save();
            }
            else{
                $finalPrice = $serviceOption->price;
            }

            // Đặt lịch hẹn dịch vụ
            $booking = $this->bookingRepository->create([
                'service_id' => $service->id,
                'coupon_id' => $couponId ?? null,
                'booking_time' => $bookingTime->format('Y-m-d H:i:s'),
                'user_id' => $bookBy->id,
                'address' => $address,
                'latitude' => $lat,
                'longitude' => $lng,
                'note' => $note,
                'price' => $finalPrice,
                'duration' => $duration->value,
                'price_before_discount' => $serviceOption->price,
                'status' => BookingStatus::PENDING->value,
            ]);
            DB::commit();
            return ServiceReturn::success(
                data: $booking
            );
        } catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi ServiceService@bookService",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * ------ Protect method ------
     */

    protected function calculateDiscount(
        float $basePrice,
        Coupon $coupon
    ): float
    {
        // Nếu là phần trăm giảm giá
        if ($coupon->is_percentage) {
            $discount = $basePrice * ($coupon->discount_value / 100);
            // Kiểm tra giảm tối đa
            if ($coupon->max_discount && $discount > $coupon->max_discount) {
                $discount = $coupon->max_discount;
            }
        } else {
            // Giảm tiền mặt cố định
            // Không được giảm quá số tiền đơn hàng (tránh âm tiền)
            $discount = min($coupon->discount_value, $basePrice);
        }
        return $discount;
    }
}
