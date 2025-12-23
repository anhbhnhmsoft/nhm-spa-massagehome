<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Enums\UserWithdrawInfoType;
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
        protected UserWithdrawInfoService $userWithdrawInfoService,
        protected UserWithdrawInfoRepository $userWithdrawInfoRepository,
    ) {
    }

    /**
     * Lấy thông tin withdraw info của user hiện tại
     */
    public function getWithdrawInfo(): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->userWithdrawInfoService->getWithdrawInfoByUserId($userId);
        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }
        return $this->sendSuccess(
            data: $result->getData()
        );
    }

    /**
     * Tạo thông tin rút tiền (withdraw info) cho user hiện tại
     */
    public function createWithdrawInfo(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'type' => ['required', 'integer', Rule::in(UserWithdrawInfoType::cases())],
            'config' => ['required', 'array'],
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
     * Tạo yêu cầu rút tiền
     */
    public function requestWithdraw(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'user_withdraw_info_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(
                message: __('validation.error'),
                errors: $validator->errors()->toArray()
            );
        }

        $data = $validator->validated();

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

