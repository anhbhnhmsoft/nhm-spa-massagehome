<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Http\Requests\ListKtvRequest;
use App\Http\Resources\Auth\UserResource;
use App\Services\AgencyService;
use Illuminate\Http\JsonResponse;

class AgencyController extends BaseController
{
    public function __construct(
        protected AgencyService $agencyService
    ) {}
    /**
     * Lấy danh sách KTV của đại lý đang quản lý
     * @return JsonResponse
     */
    public function listKtv(ListKtvRequest $request): JsonResponse
    {
        $filterDTO = $request->getFilterOptions();
        $result = $this->agencyService->manageKtv($filterDTO);
        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }
        return $this->sendSuccess(data: UserResource::collection($result->getData())->toArray($request));
    }
}
