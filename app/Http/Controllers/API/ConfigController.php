<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Enums\UserRole;
use App\Services\ConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ConfigController extends BaseController
{
    public function __construct(
        protected ConfigService $configService,
    ) {
    }

    /**
     * Lấy danh sách các kênh hỗ trợ
     */
    public function getSupportChannels(): JsonResponse
    {
        $result = $this->configService->getSupportChannels();

        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }
        return $this->sendSuccess(
            data: $result->getData(),
        );
    }


    /**
     * Lấy cấu hình affiliate dựa trên vai trò của người dùng
     * @return JsonResponse
     */
    public function getConfigAffiliate(): JsonResponse
    {
        $user = Auth::user();
        $result = $this->configService->getAffiliateConfigByRole(
            role: UserRole::from($user->role),
        );
        if ($result->isError()) {
            return $this->sendError(message: $result->getMessage());
        }
        return $this->sendSuccess(
            data: $result->getData(),
        );
    }
}

