<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Http\Resources\Review\ReviewResource;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends BaseController
{
    public function __construct(
        protected ReviewService $reviewService,
    ) {
    }

    /**
     * Tạo đánh giá cho booking dịch vụ
     */
    public function create(Request $request): JsonResponse
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

    public function listReview(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $result = $this->reviewService->reviewPaginate($dto);
        $data = $result->getData();
        return $this->sendSuccess(data: ReviewResource::collection($data)->response()->getData());
    }
}

