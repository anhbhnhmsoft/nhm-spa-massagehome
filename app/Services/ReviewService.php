<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\Language;
use App\Repositories\BookingRepository;
use App\Repositories\ReviewRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ReviewService extends BaseService
{
    public function __construct(
        protected ReviewRepository $reviewRepository,
        protected BookingRepository $bookingRepository,
        protected GeminiService      $geminiService,
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
        return $this->execute(function () use ($serviceBookingId, $rating, $comment, $hidden) {
            $user = Auth::user();
            if (!$serviceBookingId) {
                return ServiceReturn::error(message: __('validation.service_booking_id.required'));
            }

            // Kiểm tra booking có tồn tại không
            $booking = $this->bookingRepository
                ->query()
                ->where('id', $serviceBookingId)
                ->where('status', BookingStatus::COMPLETED->value)
                ->first();
            if (!$booking) {
                throw new ServiceException(message: __('common_error.data_not_found'));
            }

            // Kiểm tra user có quyền đánh giá booking này không (phải là customer của booking)
            if ((string) $booking->user_id !== (string) $user->id) {
                throw new ServiceException(message: __('common_error.unauthorized'));
            }

            // Kiểm tra đã đánh giá chưa (không cho đánh giá lại)
            $existingReview = $this->reviewRepository->query()
                ->where('service_booking_id', $serviceBookingId)
                ->where('review_by', $user->id)
                ->first();

            if ($existingReview) {
                throw new ServiceException(
                    message: __('review.error.already_reviewed')
                );
            }

            // Tạo review
            $review = $this->reviewRepository->create([
                'user_id' => $booking->ktv_user_id, // KTV nhận đánh giá
                'review_by' =>  $booking->user_id,
                'service_booking_id' => $booking->id,
                'rating' => $rating,
                'comment' => $comment,
                'review_at' => now(),
                'hidden' => $hidden,
            ]);


            return ServiceReturn::success(
                data: $review->load('recipient', 'reviewer', 'serviceBooking'),
                message: __('review.success.created')
            );
        });
    }

    /**
     * Lấy danh sách đánh giá của dịch vụ
     * @param FilterDTO $dto
     * @return ServiceReturn
     */
    public function reviewPaginate(FilterDTO $dto): ServiceReturn
    {
        try {
            $query = $this->reviewRepository->queryReview();
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

    /**
     * Translate review
     * @param int $reviewId
     * @param Language $lang
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function translateReview(int $reviewId, Language $lang): ServiceReturn
    {
        return $this->execute(function () use ($reviewId, $lang) {
            $review = $this->reviewRepository->find($reviewId);
            if (!$review) {
                throw new ServiceException(message: __('common_error.data_not_found'));
            }
            // Kiểm tra nếu comment rỗng
            $comment = trim($review->comment);
            if (!empty($comment)) {
                return [
                    'translate' => '',
                ];
            }
            // Kiểm tra nếu comment đã được dịch trước đó
            $translate = $review->getTranslation('comment_translated', $lang->value,false);
            if ($translate) {
                return [
                    'translate' => $translate,
                ];
            }
            $translate = $this->geminiService->translate(
                text: $comment,
                lang: $lang,
            );
            // Lưu translation vào review
            $review->setTranslation('comment_translated', $lang->value, $translate);
            $review->save();
            return [
                'translate' => $translate,
            ];
        });
    }
}

