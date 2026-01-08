<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Core\LogHelper;
use App\Http\Requests\FormServiceRequest;
use App\Http\Resources\Booking\BookingItemResource;
use App\Http\Resources\Review\ReviewResource;
use App\Http\Resources\Service\CategoryResource;
use App\Http\Resources\Service\DetailServiceResource;
use App\Http\Resources\Service\ServiceResource;
use App\Http\Resources\TotalIncome\TotalIncomeResource;
use App\Http\Resources\User\ProfileKTVResource;
use App\Http\Resources\User\UserFileResource;
use App\Services\BookingService;
use App\Services\ServiceService;
use App\Services\UserFileService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class KTVController extends BaseController
{
    public function __construct(
        protected UserService    $userService,
        protected BookingService $bookingService,
        protected ServiceService $serviceService,
        protected UserFileService $userFileService,
    ) {
        /**
         * Tất cả các endpoint trong controller này đều yêu cầu quyền KTV, qua validate middleware CheckKtv
         */
    }

    /**
     * Lấy thông tin dashboard của KTV
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        $result = $this->userService->dashboardKtv();
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        $booking = $data['booking'];
        $totalRevenueToday = $data['total_revenue_today'];
        $totalRevenueYesterday = $data['total_revenue_yesterday'];
        $totalBookingCompletedToday = $data['total_booking_completed_today'];
        $totalBookingPendingToday = $data['total_booking_pending_today'];
        $reviewToday = $data['review_today'];
        return $this->sendSuccess(
            data: [
                'booking' => $booking ? new BookingItemResource($booking) : null,
                'total_revenue_today' => $totalRevenueToday,
                'total_revenue_yesterday' => $totalRevenueYesterday,
                'total_booking_completed_today' => $totalBookingCompletedToday,
                'total_booking_pending_today' => $totalBookingPendingToday,
                'review_today' => $reviewToday ? ReviewResource::collection($reviewToday)->toArray($request) : null,
            ],
            message: $result->getMessage() ?? __('common.success.data_created')
        );
    }

    /**
     * Lấy danh sách booking của KTV
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listBooking(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $dto->addFilter('ktv_user_id', $request->user()->id);
        $dto->setSortBy('booking_time');
        $dto->setDirection('desc');
        $result = $this->bookingService->bookingPaginate($dto);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: BookingItemResource::collection($data)->response()->getData(),
        );
    }

    /**
     * Lấy danh sách service của KTV
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listService(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $dto->addFilter('user_id', $request->user()->id);
        $dto->setSortBy('created_at');
        $dto->setDirection('desc');
        $result = $this->serviceService->servicePaginate($dto);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: ServiceResource::collection($data)->response()->getData(),
        );
    }

    /**
     * Lấy tất cả categories
     * @param Request $request
     * @return JsonResponse
     */
    public function allCategories(Request $request): JsonResponse
    {
        $result = $this->serviceService->allCategories();
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: CategoryResource::collection($data)->toArray($request),
        );
    }

    /**
     * Thêm dịch vụ mới
     * @param FormServiceRequest $request
     * @return JsonResponse
     */
    public function addService(FormServiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $result = $this->serviceService->createService($data);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: new ServiceResource($data),
        );
    }

    /**
     * Cập nhật dịch vụ
     * @param FormServiceRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function updateService(FormServiceRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $result = $this->serviceService->updateService($id, $data);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: new ServiceResource($data),
        );
    }

    /**
     * Xóa dịch vụ
     * @param int $id
     * @return JsonResponse
     */
    public function deleteService(int $id): JsonResponse
    {
        $result = $this->serviceService->deleteService($id);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        return $this->sendSuccess(
            message: $result->getMessage(),
        );
    }

    /**
     * Lấy thông tin chi tiết dịch vụ
     * @param string $id
     * @return JsonResponse
     */
    public function detailService(string $id): JsonResponse
    {
        $result = $this->serviceService->detailService($id);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: new DetailServiceResource($data),
        );
    }
    /**
     * Lấy tổng thu nhập
     * @param Request $request
     * @return JsonResponse
     */
    public function totalIncome(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type'      => 'required|in:day,week,month,quarter,year',
        ], [
            'type.in' => __('validation.type.in'),
            'type.required' => __('validation.type.required'),
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(
                errors: $validator->errors()->toArray(),
            );
        }

        $validatedData = $validator->validated();

        $result = $this->bookingService->totalIncome($request->user(), $validatedData['type']);

        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }

        $incomeData = $result->getData();

        return $this->sendSuccess(data: new TotalIncomeResource($incomeData));
    }

    /**
     * Lấy profile KTV
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $this->sendSuccess(data: new ProfileKTVResource($user));
    }

    /**
     * Update profile KTV
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function editProfileKtv(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'bio.vi' => 'nullable|string',
                'bio.en' => 'nullable|string',
                'bio.cn' => 'nullable|string',
                'experience' => 'nullable|integer',
                'old_pass' => 'nullable|string',
                'new_pass' => 'required_with:old_pass|string|min:6',
                'lat' => 'nullable|numeric',
                'lng' => 'nullable|numeric',
                'address' => 'nullable|string',
                'gender' => 'nullable|in:1,2',
                'date_of_birth' => 'nullable|date',
            ],
            [
                'date_of_birth.date' => __('validation.date_of_birth.date'),
                'date_of_birth.date_format' => __('validation.date_of_birth.date_format'),
                'experience.integer' => __('validation.experience.integer'),
                'old_pass.string' => __('validation.old_pass.string'),
                'new_pass.string' => __('validation.new_pass.string'),
                'lat.numeric' => __('validation.lat.numeric'),
                'lng.numeric' => __('validation.lng.numeric'),
                'address.string' => __('validation.address.string'),
                'gender.in' => __('validation.gender.in'),
                'bio.vi.string' => __('validation.bio.vi.string'),
                'bio.en.string' => __('validation.bio.en.string'),
                'bio.cn.string' => __('validation.bio.cn.string'),
            ]
        );

        if ($validator->fails()) {
            return $this->sendValidation(
                errors: $validator->errors()->toArray(),
            );
        }

        $data = $validator->validated();
        LogHelper::debug(
            message: "KTVController@editProfileKtv",
            context: $data,
        );
        $res = $this->userService->updateKtvProfile($data);
        if ($res->isError()) {
            return $this->sendError($res->getMessage());
        }
        return $this->sendSuccess(
            data: new ProfileKTVResource($res->getData()),
            message: __('admin.notification.success.update_success')
        );
    }

    /**
     * Upload KTV images (Display)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadKtvImages(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1',
            'images.*' => 'required|file|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ], [
            'images.required' => __('validation.images.required'),
            'images.array' => __('validation.images.array'),
            'images.min' => __('validation.images.min', ['min' => 1]),
            'images.*.required' => __('validation.images.required'),
            'images.*.image' => __('validation.images.image'),
            'images.*.mimes' => __('validation.images.mimes'),
            'images.*.max' => __('validation.images.max', ['max' => '10mb']),
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(
                errors: $validator->errors()->toArray(),
            );
        }
        $images = $request->file('images');
        $result = $this->userFileService->uploadKtvImages($images);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: UserFileResource::collection($result->getData()),
            message: __('admin.ktv.messages.upload_success')
        );
    }

    /**
     * Delete KTV image
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteKtvImage(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $result = $this->userFileService->deleteUserFile($id, $user->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(message: __('admin.ktv.messages.delete_success'));
    }

    public function getSchedule(): JsonResponse
    {
        $user = Auth::user();
        $result = $this->userService->handleGetScheduleKtv($user->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: new KtvScheduleResource($result->getData()),
            message: __('admin.notification.success.get_success')
        );
    }
}
