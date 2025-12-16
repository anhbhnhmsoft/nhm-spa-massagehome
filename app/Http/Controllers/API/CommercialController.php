<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Http\Resources\Commercial\BannerResource;
use App\Services\CommercialService;

class CommercialController extends BaseController
{

    public function __construct(
        protected CommercialService $commercialService,
    )
    {

    }

    /**
     * Lấy danh sách banner cho homepage
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBanner()
    {
        $result = $this->commercialService->getBanner();
        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }
        $data = $result->getData();
        return $this->sendSuccess(data: BannerResource::collection($data));

    }



}
