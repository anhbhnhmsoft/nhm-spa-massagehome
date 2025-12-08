<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Http\Resources\Service\CategoryResource;
use App\Http\Resources\Service\ServiceResource;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


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

    /**
     * Lấy chi tiết dịch vụ
     * @param int $id
     * @return JsonResponse
     */
    public function detailService(int $id): JsonResponse
    {
        $result = $this->serviceService->getDetailService($id);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: new ServiceResource($data)
        );
    }

    public function bookService(Request $request)
    {

    }

}
