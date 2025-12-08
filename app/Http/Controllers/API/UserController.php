<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Http\Resources\User\ListKTVResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    public function __construct(
        protected UserService $userService
    )
    {
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
            data: new ListKTVResource($data)
        );
    }
}
