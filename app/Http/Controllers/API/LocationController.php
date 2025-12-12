<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Http\Resources\Location\PlaceDetailResource;
use App\Http\Resources\Location\PlacePredictionResource;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LocationController extends BaseController
{

    public function __construct(
        protected LocationService $locationService
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
            'keyword.required' => __('error.location.validate.keyword_required'),
            'keyword.string' => __('error.location.validate.keyword_string'),
            'latitude.numeric' => __('error.location.validate.latitude_numeric'),
            'longitude.numeric' => __('error.location.validate.longitude_numeric'),
            'radius.numeric' => __('error.location.validate.radius_numeric'),
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
            'place_id.required' => __('error.location.validate.place_id_required'),
            'place_id.string' => __('error.location.validate.place_id_string'),
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
}
