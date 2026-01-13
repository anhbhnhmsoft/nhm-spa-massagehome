<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Http\Requests\API\Agency\ListKtvRequest;
use App\Http\Resources\Auth\UserResource;
use App\Services\AgencyService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AgencyController extends BaseController
{
    public function __construct(
        protected AgencyService $agencyService,
        protected UserService $userService
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
        return $this->sendSuccess(data: UserResource::collection($result->getData())->response()->getData(true));
    }

    /**
     * Link KTV to Agency via QR
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function linkQr(\Illuminate\Http\Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'agency_id' => 'required|integer|exists:users,id'
        ],
        [
            'agency_id.required' => __('error.verify_agency'),
            'agency_id.integer' => __('error.verify_agency'),
            'agency_id.exists' => __('error.verify_agency'),
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return $this->sendError(__('common_error.unauthenticated'));
        }

        $result = $this->userService->linkKtvToAgency($user->id, $request->input('agency_id'));
        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: [
                'ktv' => new UserResource($result->getData()['ktv']),
                'is_ktv' => $result->getData()['is_ktv'] ?? false,
            ],
            message: $result->getMessage()
        );
    }
}
