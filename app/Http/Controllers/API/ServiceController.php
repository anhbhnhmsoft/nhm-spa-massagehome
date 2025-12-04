<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Http\Resources\Service\CategoryResource;
use App\Http\Resources\Service\ServiceResource;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;

class ServiceController extends BaseController
{
    public function __construct(
        protected ServiceService $serviceService,
    )
    {
    }

    /**
     * Lấy danh sách danh mục dịch vụ
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listCategory(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();

        $result = $this->serviceService->getListCategory($dto);
        $data = $result->getData();
        return $this->sendSuccess(
            data: CategoryResource::collection($data)->response()->getData()
        );
    }
    /**
     * Lấy danh sách dịch vụ
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listServices(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $result = $this->serviceService->getListService($dto);
        $data = $result->getData();
        return $this->sendSuccess(
            data: ServiceResource::collection($data)->response()->getData()
        );
    }


}
