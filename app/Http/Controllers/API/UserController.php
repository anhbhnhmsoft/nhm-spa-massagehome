<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Http\Resources\User\AddressResource;
use App\Http\Resources\User\CustomerBookedTodayResource;
use App\Http\Resources\User\ItemKTVResource;
use App\Http\Resources\User\ListAddressResource;
use App\Http\Resources\User\ListKTVResource;
use App\Services\UserService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function __construct(
        protected UserService $userService
    ) {}

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
     * Đăng ký cộng tác viên/đối tác
     */
    public function registerAgency(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6'],
            'reviewApplication.province_code' => ['nullable', 'string', 'max:50', 'exists:provinces,code'],
            'reviewApplication.address' => ['nullable', 'string', 'max:255'],
            'reviewApplication.bio' => ['nullable', 'string'],
            'files' => ['nullable', 'array'],
            'files.*.type' => ['nullable', 'integer'],
            'files.*.file_path' => ['required_with:files.*', 'string'],
        ], [
            'name.required' => __('validation.required', ['attribute' => 'name']),
            'phone.required' => __('validation.required', ['attribute' => 'phone']),
            'password.required' => __('validation.required', ['attribute' => 'password']),
            'password.min' => __('validation.min.string', ['attribute' => 'password', 'min' => 6]),
            'files.*.file_path.required_with' => __('validation.required', ['attribute' => 'file_path']),
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(
                message: __('validation.error'),
                errors: $validator->errors()->toArray()
            );
        }

        $data = $validator->validated();
        $result = $this->userService->makeNewApplyAgency($data);

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
    public function applyPartner(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'integer', 'in:2,3'], // 2 = KTV, 3 = AGENCY
            'reviewApplication.agency_id' => ['nullable', 'integer', 'exists:users,id'],
            'reviewApplication.province_code' => ['nullable', 'string', 'max:50', 'exists:provinces,code'],
            'reviewApplication.address' => ['nullable', 'string', 'max:255'],
            'reviewApplication.bio' => ['nullable', 'string'],
            'files' => ['nullable', 'array'],
            'files.*.type' => ['nullable', 'integer'],
            'files.*.file_path' => ['required_with:files.*', 'string'],
            'files.*.is_public' => ['nullable', 'boolean'],
        ], [
            'role.required' => __('validation.required', ['attribute' => 'role']),
            'role.in' => __('validation.in', ['attribute' => 'role']),
            'reviewApplication.province_code.exists' => __('validation.exists', ['attribute' => 'province_code']),
            'files.*.file_path.required_with' => __('validation.required', ['attribute' => 'file_path']),
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(
                message: __('validation.error'),
                errors: $validator->errors()->toArray()
            );
        }

        $data = $validator->validated();
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
     * Lấy danh sách KTV
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listKtv(ListRequest $request): \Illuminate\Http\JsonResponse
    {
        $dto = $request->getFilterOptions();

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
            data: new ItemKTVResource($data['ktv'], $data['break_time_gap'])
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
            'is_primary' => ['nullable', 'boolean'],
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
            'is_primary.boolean' => __('validation.location.is_primary_boolean'),
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
            'is_primary' => ['nullable', 'boolean'],
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
            'is_primary.boolean' => __('validation.location.is_primary_boolean'),
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
        ]);
        $dto->setSortBy('is_primary');
        $dto->setDirection('desc');

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
}
