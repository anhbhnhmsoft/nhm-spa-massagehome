<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Core\LogHelper;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Http\Requests\ApplyPartnerRequest;
use App\Http\Requests\ListKTVRequest;
use App\Http\Resources\User\AddressResource;
use App\Http\Resources\User\ItemKTVResource;
use App\Http\Resources\User\ListAddressResource;
use App\Http\Resources\User\ListKTVResource;
use App\Http\Resources\User\UserReviewApplicationResource;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function __construct(
        protected UserService $userService
    )
    {
    }

    /**
     * Lấy thông tin dashboard profile của user hiện tại
     * @return JsonResponse
     */
    public function dashboardProfile()
    {
        $result = $this->userService->dashboardProfile();

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }

        return $this->sendSuccess(
            data: $result->getData(),
            message: $result->getMessage() ?? __('common.success.data_created')
        );
    }

    /**
     * User hiện tại đăng ký làm đối tác (tạo hồ sơ chờ duyệt).
     */
    public function applyPartner(ApplyPartnerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->userService->applyPartnerForCurrentUser($data);

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }

        return $this->sendSuccess(
            data: $result->getData(),
            message: $result->getMessage() ?? __('common.success.data_created')
        );
    }

    /**
     * Kiểm tra thông tin đăng ký đối tác (KTV hoặc Agency)
     * @return JsonResponse
     */
    public function checkApplyPartner()
    {
        $result = $this->userService->checkApplyPartnerForCurrentUser();

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: [
                'can_apply' => $data['check_apply'],
                'review_application' => $data['review_application'] ? new UserReviewApplicationResource($data['review_application']) : null,
            ],
            message: $result->getMessage() ?? __('common.success.data_created')
        );
    }

    /**
     * Lấy danh sách KTV
     * @param ListKTVRequest $request
     * @return JsonResponse
     */
    public function listKtv(ListKTVRequest $request): \Illuminate\Http\JsonResponse
    {
        $dto = $request->getFilterOptions();

        $result = $this->userService->paginationKTV(dto: $dto);
        $data = $result->getData();
        return $this->sendSuccess(
            data: ListKTVResource::collection($data)->response()->getData()
        );
    }

    /**
     * Lấy danh sách KTV được quản lý bởi Agency hoặc KTV
     * @param ListKTVRequest $request
     * @return JsonResponse
     */
    public function listKtvManager(ListKTVRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $dto->addFilter('referrer_id', Auth::user()->id);
        $result = $this->userService->paginationKTV(dto: $dto);
        $data = $result->getData();
        return $this->sendSuccess(
            data: ListKTVResource::collection($data)->response()->getData()
        );
    }

    /**
     * Lấy thông tin KTV
     * @param int $id
     * @return JsonResponse
     */
    public function detailKtv(int $id): \Illuminate\Http\JsonResponse
    {
        $result = $this->userService->getKtvById(id: $id);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: new ItemKTVResource($data['ktv'], $data['break_time_gap'], $data['price_transportation'])
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function saveAddress(Request $request): \Illuminate\Http\JsonResponse
    {
        $validate = $request->validate([
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'desc' => ['nullable', 'string'],
        ], [
            'address.required' => __('validation.location.address_required'),
            'address.string' => __('validation.location.address_string'),
            'latitude.required' => __('validation.location.latitude_required'),
            'latitude.numeric' => __('validation.location.latitude_numeric'),
            'latitude.between' => __('validation.location.latitude_between'),
            'longitude.required' => __('validation.location.longitude_required'),
            'longitude.numeric' => __('validation.location.longitude_numeric'),
            'longitude.between' => __('validation.location.longitude_between'),
            'desc.string' => __('validation.location.desc_string'),
        ]);
        $validate['user_id'] = $request->user()->id;


        $result = $this->userService->saveAddress(data: $validate);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: new AddressResource($data)
        );
    }

    /**
     * Cập nhật thông tin địa chỉ
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function editAddress(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $validate = $request->validate([
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'desc' => ['nullable', 'string'],
        ], [
            'address.required' => __('validation.location.address_required'),
            'address.string' => __('validation.location.address_string'),
            'latitude.required' => __('validation.location.latitude_required'),
            'latitude.numeric' => __('validation.location.latitude_numeric'),
            'latitude.between' => __('validation.location.latitude_between'),
            'longitude.required' => __('validation.location.longitude_required'),
            'longitude.numeric' => __('validation.location.longitude_numeric'),
            'longitude.between' => __('validation.location.longitude_between'),
            'desc.string' => __('validation.location.desc_string'),
        ]);
        $result = $this->userService->editAddress(id: $id, data: $validate);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: new AddressResource($data)
        );
    }

    /**
     * Xóa địa chỉ
     * @param string $id
     * @return JsonResponse
     */
    public function deleteAddress(string $id): \Illuminate\Http\JsonResponse
    {
        $result = $this->userService->deleteAddress(id: $id);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        return $this->sendSuccess();
    }

    /**
     * Lấy danh sách địa chỉ
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listAddress(ListRequest $request): \Illuminate\Http\JsonResponse
    {
        $dto = $request->getFilterOptions();
        $dto->setFilters([
            'user_id' => $request->user()->id,
            'is_primary' => false
        ]);

        $result = $this->userService->getPaginateAddress(dto: $dto);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: ListAddressResource::collection($data)->response()->getData()
        );
    }

    /**
     * Cập nhật địa chỉ mặc định
     * @param Request $request
     * @return JsonResponse
     */
    public function setDefaultAddress(Request $request): \Illuminate\Http\JsonResponse
    {
        $validate = $request->validate([
            'address' => ['required', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'address.required' => __('validation.location.address_required'),
            'address.string' => __('validation.location.address_string'),
            'latitude.required' => __('validation.location.latitude_required'),
            'latitude.numeric' => __('validation.location.latitude_numeric'),
            'latitude.between' => __('validation.location.latitude_between'),
            'longitude.required' => __('validation.location.longitude_required'),
            'longitude.numeric' => __('validation.location.longitude_numeric'),
            'longitude.between' => __('validation.location.longitude_between'),
        ]);
        $userId = Auth::id();

        $result = $this->userService->setDefaultAddress(
            userId: $userId,
            latitude: $validate['latitude'],
            longitude: $validate['longitude'],
            address: $validate['address'],
        );
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        return $this->sendSuccess();
    }

}
