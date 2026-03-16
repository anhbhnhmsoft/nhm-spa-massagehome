<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Enums\Language;
use App\Enums\UserRole;
use App\Http\Resources\Review\ReviewResource;
use App\Http\Resources\Service\CategoryResource;
use App\Http\Resources\Service\CouponResource;
use App\Http\Resources\Service\CouponUserResource;
use App\Services\BookingService;
use App\Services\ReviewService;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class ServiceController extends BaseController
{
    public function __construct(
        protected ServiceService $serviceService,
        protected BookingService $bookingService,
        protected ReviewService $reviewService,
    )
    {
    }

    /**
     * Lấy danh sách danh mục dịch vụ
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listCategory(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $result = $this->serviceService->categoryPaginate($dto);
        $data = $result->getData();
        return $this->sendSuccess(
            data: CategoryResource::collection($data)->response()->getData()
        );
    }

    /**
     * Lấy danh sách mã giảm giá
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listCoupon(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        // Lọc chỉ lấy mã giảm giá hợp lệ
        $dto->addFilter('is_valid', true);
        // Lấy toàn bộ mã giảm giá (không phân trang)
        $dto->addFilter('get_all', true);
        // Lọc chỉ lấy mã giảm giá chưa được sử dụng
        $dto->addFilter('user_id_is_not_used', $request->user()->id);

        $result = $this->serviceService->getListCoupon($dto);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: CouponResource::collection($data)
        );
    }

    /**
     * Lấy danh sách mã giảm giá của người dùng
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function myListCoupon(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        // Lọc chỉ lấy mã giảm giá chưa được sử dụng
        $dto->addFilter('is_used', false);
        // Lấy toàn bộ mã giảm giá của người dùng
        $dto->addFilter('user_id', $request->user()->id);

        $result = $this->serviceService->couponUserPaginate($dto);

        $data = $result->getData();

        return $this->sendSuccess(
            data: CouponUserResource::collection($data)->response()->getData()
        );
    }

    /**
     * Tạo đánh giá cho dịch vụ
     * @param Request $request
     * @return JsonResponse
     */
    public function createReview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_booking_id' => ['required', 'numeric', 'exists:service_bookings,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'hidden' => ['nullable', 'boolean'],
        ], [
            'service_booking_id.required' => __('validation.service_booking_id.required'),
            'service_booking_id.numeric' => __('validation.service_booking_id.numeric'),
            'service_booking_id.exists' => __('validation.service_booking_id.exists'),
            'rating.required' => __('validation.rating.required'),
            'rating.integer' => __('validation.rating.integer'),
            'rating.min' => __('validation.rating.min'),
            'rating.max' => __('validation.rating.max'),
            'hidden.boolean' => __('validation.hidden.boolean'),
        ]);

        $result = $this->reviewService->createReview(
            serviceBookingId: $data['service_booking_id'],
            rating: $data['rating'],
            comment: $data['comment'],
            hidden: $data['hidden'] ?? false
        );

        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }

        return $this->sendSuccess(
            data: new ReviewResource($result->getData()),
        );
    }

    /**
     * Lấy danh sách đánh giá của dịch vụ
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listReview(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $user = Auth::user();

        // Nếu là KTV, chỉ hiển thị đánh giá của KTV đó
        if ($user && $user->role === UserRole::KTV->value){
            $dto->addFilter('user_id', $user->id);
        }
        $result = $this->reviewService->reviewPaginate($dto);
        $data = $result->getData();
        return $this->sendSuccess(data: ReviewResource::collection($data)->response()->getData());
    }

    public function translateReview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'review_id' => ['required', 'numeric', 'exists:reviews,id'],
            'lang' => ['required', Rule::enum(Language::class)],
        ], [
            'review_id.required' => __('validation.review_id.required'),
            'review_id.numeric' => __('validation.review_id.invalid'),
            'review_id.exists' => __('validation.review_id.invalid'),
            'lang.required' => __('validation.lang.required'),
            'lang.enum' => __('validation.lang.invalid'),
        ]);
        $result = $this->reviewService->translateReview(
            reviewId: $data['review_id'],
            lang: Language::from($data['lang']),
        );
        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }
        return $this->sendSuccess(
            data: [
                'translate' => $result->getData()['translate'] ?? "",
            ],
        );
    }

}
