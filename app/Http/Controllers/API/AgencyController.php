<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Http\Resources\Auth\UserResource;
use App\Services\AgencyService;
use Illuminate\Http\JsonResponse;

class AgencyController extends BaseController
{
    public function __construct(
        protected AgencyService $agencyService)
    {
    }
    /**
     * Lấy danh sách KTV của đại lý đang quản lý
     * @return \Illuminate\Http\JsonResponse
     */
    public function listKtv() : JsonResponse
    {
        $result = $this->agencyService->manageKtv();
        if($result->isError()){
            return $this->sendError($result->getMessage());
        }
        return $this->sendSuccess(data: UserResource::collection($result->getData()));
    }
}
