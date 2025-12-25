<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Enums\UserWithdrawInfoType;
use App\Http\Resources\Payment\UserWithdrawInfoResource;
use App\Repositories\UserWithdrawInfoRepository;
use App\Services\UserWithdrawInfoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WithdrawController extends BaseController
{
    public function __construct(
        protected UserWithdrawInfoService    $userWithdrawInfoService,
        protected UserWithdrawInfoRepository $userWithdrawInfoRepository,
    )
    {
    }

    /**
     * Lấy thông tin withdraw info của user hiện tại
     */
    public function getWithdrawInfo(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->userWithdrawInfoService->getWithdrawInfoByUserId($userId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: UserWithdrawInfoResource::collection($data)->toArray($request),
        );
    }

    /**
     * Tạo thông tin rút tiền (withdraw info) cho user hiện tại
     */
    public function createWithdrawInfo(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in(UserWithdrawInfoType::cases())],
            'config' => ['required', 'array', function (string $attribute, mixed $value, callable $fail) use ($request) {
                $userWithdrawInfoType = UserWithdrawInfoType::from((int)$request->input('type'));
                $requiredFields = UserWithdrawInfoType::getConfig($userWithdrawInfoType);
                foreach ($requiredFields as $field) {
                    if (!isset($value[$field])) {
                        $fail(__('validation.config_withdraw_info.missing_field'));
                    }
                }
            }],
        ], [
            'type.required' => __('validation.type_withdraw_info.required'),
            'type.in' => __('validation.type_withdraw_info.in'),
            'config.required' => __('validation.config_withdraw_info.required'),
            'config.array' => __('validation.config_withdraw_info.invalid'),
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(
                message: __('validation.error'),
                errors: $validator->errors()->toArray()
            );
        }

        $data = $validator->validated();
        $result = $this->userWithdrawInfoService->createWithdrawInfo(
            userId: $userId,
            type: (int)$data['type'],
            config: $data['config'],
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(data: $result->getData(), message: $result->getMessage());
    }

    /**
     * Xóa thông tin rút tiền của user hiện tại
     * @param int $id
     * @return JsonResponse
     */
    public function deleteWithdrawInfo(int $id): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->userWithdrawInfoService->deleteWithdrawInfo(
            userId: $userId,
            withdrawInfoId: $id,
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }
        return $this->sendSuccess();
    }

    /**
     * Tạo yêu cầu rút tiền
     */
    public function requestWithdraw(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_withdraw_info_id' => ['required','numeric', 'exists:user_withdraw_infos,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ],[
            'user_withdraw_info_id.required' => __('validation.user_withdraw_info.invalid'),
            'user_withdraw_info_id.numeric' => __('validation.user_withdraw_info.invalid'),
            'user_withdraw_info_id.exists' => __('validation.user_withdraw_info.invalid'),
            'amount.required' => __('validation.amount.required'),
            'amount.numeric' => __('validation.amount.numeric'),
            'amount.gt' => __('validation.amount.gt'),
            'note.max' => __('validation.note.max'),
        ]);
        $userId = Auth::id();

        $result = $this->userWithdrawInfoService->requestWithdraw(
            userId: $userId,
            withdrawInfoId: (int)$data['user_withdraw_info_id'],
            amount: (float)$data['amount'],
            note: $data['note'] ?? null,
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: $result->getData(),
            message: $result->getMessage()
        );
    }
}

