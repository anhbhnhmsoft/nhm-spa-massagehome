<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
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
            'service_booking_id' => ['required', 'string', 'exists:service_bookings,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'hidden' => ['nullable', 'boolean'],
        ], [
            'service_booking_id.required' => __('validation.service_booking_id.required', ['attribute' => 'service_booking_id']),
            'service_booking_id.exists' => __('validation.service_booking_id.exists', ['attribute' => 'service_booking_id']),
            'rating.required' => __('validation.rating.required', ['attribute' => 'rating']),
            'rating.integer' => __('validation.rating.integer', ['attribute' => 'rating']),
            'rating.min' => __('validation.rating.min', ['attribute' => 'rating']),
            'rating.max' => __('validation.rating.max', ['attribute' => 'rating']),
            'hidden.boolean' => __('validation.hidden.boolean', ['attribute' => 'hidden']),
        ]);

        $result = $this->reviewService->createReview($data);

        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }

        return $this->sendSuccess(
            data: new ReviewResource($result->getData()),
            message: $result->getMessage() ?? __('review.success.created')
        );
    }
}

