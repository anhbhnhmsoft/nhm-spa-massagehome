<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Enums\DateRangeDashboard;
use App\Http\Resources\User\ListKtvPerformanceItem;
use App\Http\Resources\User\ProfileAgencyResource;
use App\Services\AgencyService;
use App\Services\DashboardService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Lấy thông tin profile Agency
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        return $this->sendSuccess(data: new ProfileAgencyResource(Auth::user()));
    }
    /**
     * Cập nhật thông tin profile
     * @param Request $request
     * @return JsonResponse
     */
    public function editProfile(Request $request): JsonResponse
    {
        $data = $request->validate(
            [
                'bio.vi' => 'nullable|string',
                'bio.en' => 'nullable|string',
                'bio.cn' => 'nullable|string',
                'old_pass' => 'nullable|string',
                'new_pass' => 'required_with:old_pass|string|min:6',
                'gender' => 'nullable|in:1,2',
                'date_of_birth' => 'nullable|date',
            ],
            [
                'date_of_birth.date' => __('validation.date_of_birth.date'),
                'date_of_birth.date_format' => __('validation.date_of_birth.date_format'),
                'old_pass.string' => __('validation.old_pass.string'),
                'new_pass.string' => __('validation.new_pass.string'),
                'gender.in' => __('validation.gender.in'),
                'bio.vi.string' => __('validation.bio.vi.string'),
                'bio.en.string' => __('validation.bio.en.string'),
                'bio.cn.string' => __('validation.bio.cn.string'),
            ]
        );
        $data['user_id'] = Auth::user()->id;
        $res = $this->userService->updateAgencyProfile($data);
        if ($res->isError()) {
            return $this->sendError($res->getMessage());
        }
        return $this->sendSuccess(
            message: __('admin.notification.success.update_success')
        );
    }

}
