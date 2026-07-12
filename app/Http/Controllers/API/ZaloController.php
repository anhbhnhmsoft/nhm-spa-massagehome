<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Services\ZaloService;
use Illuminate\Http\JsonResponse;

class ZaloController extends BaseController
{
    public function __construct(
        protected ZaloService $zaloService,
    ) {
    }

    public function accessToken(): JsonResponse
    {
        $result = $this->zaloService->getAccessTokenForOA();

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
                code: 500,
            );
        }

        $response = $this->sendSuccess(
            data: [
                'access_token' => (string) $result->getData(),
            ],
        );

        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
