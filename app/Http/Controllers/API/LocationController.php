<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Http\Resources\Location\PlaceDetailResource;
use App\Http\Resources\Location\PlacePredictionResource;
use App\Services\LocationService;
use App\Services\ProvinceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LocationController extends BaseController
{

    public function __construct(
        protected LocationService $locationService,
        protected ProvinceService $provinceService,
    ) {}
    /**
     * search map
     */
    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'keyword' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'radius' => ['nullable', 'numeric'],
 
        ], [
            'keyword.required' => __('validation.location.keyword_required'),
            'keyword.string' => __('validation.location.keyword_string'),
            'latitude.numeric' => __('validation.location.latitude_numeric'),
            'longitude.numeric' => __('validation.location.longitude_numeric'),
            'radius.numeric' => __('validation.location.radius_numeric'),
        ]);

        $result = $this->locationService->autoComplete(
            $data['keyword'],
            $data['latitude'] ?? 0,
            $data['longitude'] ?? 0,
            10,
            $data['radius'] ?? 10
        );
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        return $this->sendSuccess(
            data: PlacePredictionResource::collection($result->getData())
        );
    }

    public function detail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'place_id' => ['required', 'string'],
        ], [
            'place_id.required' => __('validation.location.place_id_required'),
            'place_id.string' => __('validation.location.place_id_string'),
        ]);
        $result = $this->locationService->getDetail($data['place_id']);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }
        return $this->sendSuccess(
            data: PlaceDetailResource::make($result->getData())
        );
    }

    /**
     * Lấy danh sách tỉnh/thành
     */
    public function listProvinces(Request $request): JsonResponse
    {
        $data = $request->validate([
            'keyword' => ['nullable', 'string'],
        ]);

        $result = $this->provinceService->getProvinces($data['keyword'] ?? null);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }

        return $this->sendSuccess(
            data: $result->getData(),
            message: __('common.success.data_list')
        );
    }
}
