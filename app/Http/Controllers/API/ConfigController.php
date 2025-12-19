<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Services\ConfigService;
use Illuminate\Http\JsonResponse;

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
}

