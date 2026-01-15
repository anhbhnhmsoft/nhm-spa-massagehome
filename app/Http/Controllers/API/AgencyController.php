<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Enums\DateRangeDashboard;
use App\Http\Requests\API\Agency\ListKtvRequest;
use App\Http\Resources\Auth\UserResource;
use App\Http\Resources\User\ListKtvPerformanceItem;
use App\Services\AgencyService;
use App\Services\DashboardService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AgencyController extends BaseController
{
    public function __construct(
        protected AgencyService $agencyService,
        protected UserService $userService,
        protected DashboardService $dashboardService,
    ) {}

    /**
     * Lấy dữ liệu dashboard tổng quan cho Agency
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboard(Request $request)
    {
        $validator = $request->validate([
            'range' => ['required', Rule::in(DateRangeDashboard::values())],
        ], [
            'range.in' => __('validation.type_date_range.in'),
            'range.required' => __('validation.type_date_range.required'),
        ]);
        $data = $this->dashboardService->getAgencyDashboardData(
            userId: $request->user()->id,
            range: DateRangeDashboard::from($validator['range']),
        );
        if ($data->isError()) {
            return $this->sendError(
                message: $data->getMessage(),
            );
        }
        return $this->sendSuccess(
            data: $data->getData(),
        );
    }

    /**
     * Lấy danh sách KTV Performance
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listKtvPerformance(ListRequest $request)
    {
        $dto = $request->getFilterOptions();
        $range = $dto->findFilter('range');
        if (!$range || !DateRangeDashboard::tryFrom($range)) {
            $range = DateRangeDashboard::ALL->value;
        }

        $data = $this->dashboardService->getListKtvPerformancePaginated(
            userId: $request->user()->id,
            range: DateRangeDashboard::from($range),
            page: $dto->page,
            limit: $dto->perPage,
        );
        if ($data->isError()) {
            return $this->sendError(
                message: $data->getMessage(),
            );
        }
        $paginator = $data->getData();
        return $this->sendSuccess(
            data: ListKtvPerformanceItem::collection($paginator)->response()->getData(true),
        );
    }

    public function listKtv(ListKtvRequest $request): JsonResponse
    {
        $filterDTO = $request->getFilterOptions();
        $result = $this->agencyService->manageKtv($filterDTO);
        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }
        return $this->sendSuccess(data: UserResource::collection($result->getData())->response()->getData(true));
    }


}
