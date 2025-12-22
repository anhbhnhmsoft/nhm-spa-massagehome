<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Repositories\BookingRepository;
use App\Repositories\ReviewRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewService extends BaseService
{
    public function __construct(
        protected ReviewRepository $reviewRepository,
        protected BookingRepository $bookingRepository,
    ) {
        parent::__construct();
    }

    /**
     * Tạo đánh giá cho booking dịch vụ
     * Logic: Không cho đánh giá lại nếu đã có review cho service_booking_id
     */
    public function createReview(
        int $serviceBookingId,
        int $rating,
        ?string $comment = null,
        bool $hidden = false
    ): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.unauthorized'));
            }

            if (!$serviceBookingId) {
                return ServiceReturn::error(message: __('validation.required', ['attribute' => 'service_booking_id']));
            }

            // Kiểm tra booking có tồn tại không
            $booking = $this->bookingRepository->find($serviceBookingId);
            if (!$booking) {
                return ServiceReturn::error(message: __('common_error.data_not_found'));
            }

            // Kiểm tra user có quyền đánh giá booking này không (phải là customer của booking)
            if ((string) $booking->user_id !== (string) $user->id) {
                return ServiceReturn::error(message: __('common_error.unauthorized'));
            }

            // Kiểm tra đã đánh giá chưa (không cho đánh giá lại)
            $existingReview = $this->reviewRepository->query()
                ->where('service_booking_id', $serviceBookingId)
                ->where('review_by', $user->id)
                ->first();

            if ($existingReview) {
                return ServiceReturn::error(
                    message: __('review.error.already_reviewed')
                );
            }

            // Xác định user_id (người nhận review) là KTV của booking
            $ktvId = $booking->ktv_user_id;
            if (!$ktvId) {
                return ServiceReturn::error(message: __('review.error.booking_has_no_ktv'));
            }

            // Tạo review
            $review = $this->reviewRepository->create([
                'user_id' => $ktvId, // KTV nhận đánh giá
                'review_by' => $user->id, // Customer viết đánh giá
                'service_booking_id' => $serviceBookingId,
                'rating' => $rating,
                'comment' => $comment,
                'review_at' => now(),
                'hidden' => $hidden,
            ]);


            return ServiceReturn::success(
                data: $review->load('recipient', 'reviewer', 'serviceBooking'),
                message: __('review.success.created')
            );
        } catch (\Throwable $exception) {
            DB::rollBack();
            LogHelper::error(
                message: 'Lỗi ReviewService@createReview',
                ex: $exception
            );

            return ServiceReturn::error(
                message: __('common_error.server_error')
            );
        }
    }

    public function reviewPaginate(FilterDTO $dto): ServiceReturn
    {
        try {
            $query = $this->reviewRepository->query();
            $query = $this->reviewRepository->filterQuery(
                query: $query,
                filters: $dto->filters
            );
            $query = $this->reviewRepository->sortQuery(
                query: $query,
                sortBy: $dto->sortBy,
                direction: $dto->direction
            );
            $paginate = $query->paginate(
                perPage: $dto->perPage,
                page: $dto->page
            );
            return ServiceReturn::success(
                data: $paginate,
            );
        } catch (\Throwable $exception) {
            LogHelper::error(
                message: "Lỗi ReviewService@reviewPaginate",
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

