<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Enums\KtvTechnique;
use App\Enums\UrgencyLevel;
use App\Models\Category;
use App\Models\Service;
use App\Services\ServiceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceRequestController extends BaseController
{
    public function __construct(
        protected ServiceRequestService $serviceRequestService
    ) {}

    /**
     * Khách hàng tạo Yêu cầu dịch vụ mới nhờ CSKH hỗ trợ
     */
    public function store(Request $request): JsonResponse
    {
        $resolvedServiceId = $this->resolveServiceOrCategoryId($request->input('service_id'));
        if ($resolvedServiceId) {
            $request->merge(['service_id' => $resolvedServiceId]);
        }

        $validator = Validator::make($request->all(), [
            'service_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $strVal = (string) $value;
                    $existsInServices = Service::where('id', $strVal)->exists();
                    $existsInCategories = Category::where('id', $strVal)->exists();
                    if (!$existsInServices && !$existsInCategories) {
                        $fail(__('validation.exists', ['attribute' => $attribute]));
                    }
                },
            ],
            'preferred_techniques' => 'nullable|array',
            'preferred_techniques.*' => ['string', Rule::in(KtvTechnique::values())],
            'province_code' => 'nullable|string',
            'district_code' => 'nullable|string',
            'ward_code' => 'nullable|string',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'preferred_date' => 'nullable|date|after_or_equal:today',
            'time_slot' => 'nullable|string',
            'urgency_level' => ['nullable', Rule::in(UrgencyLevel::values())],
            'preferred_ktv_ids' => 'nullable|array',
            'preferred_ktv_ids.*' => 'string|exists:users,id',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(
                errors: $validator->errors()->toArray()
            );
        }

        $customerId = (string) Auth::id();
        $result = $this->serviceRequestService->createRequest($validator->validated(), $customerId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: $result->getData(),
            message: __('admin.service_request.messages.create_success')
        );
    }

    /**
     * Danh sách Yêu cầu dịch vụ của Khách hàng
     */
    public function index(Request $request): JsonResponse
    {
        $customerId = (string) Auth::id();
        $result = $this->serviceRequestService->getCustomerRequests($customerId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: $result->getData()
        );
    }

    /**
     * Khách hàng Phản hồi (Đồng ý / Từ chối) KTV do CSKH đề xuất
     */
    public function respondProposalByCustomer(Request $request, int|string $proposalId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'accept' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(
                errors: $validator->errors()->toArray()
            );
        }

        $customerId = (string) Auth::id();
        $accept = (bool) $request->input('accept');

        $result = $this->serviceRequestService->customerRespondProposal($proposalId, $customerId, $accept);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: $result->getData(),
            message: __('admin.service_request.messages.respond_success')
        );
    }

    /**
     * KTV Xem danh sách Đề xuất dịch vụ gửi cho mình
     */
    public function ktvProposals(Request $request): JsonResponse
    {
        $ktvId = (string) Auth::id();
        $result = $this->serviceRequestService->getKtvProposals($ktvId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: $result->getData()
        );
    }

    /**
     * KTV Phản hồi Lời mời đề xuất (Đồng ý / Từ chối)
     */
    public function respondProposalByKtv(Request $request, int|string $proposalId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'accept' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(
                errors: $validator->errors()->toArray()
            );
        }

        $ktvId = (string) Auth::id();
        $accept = (bool) $request->input('accept');

        $result = $this->serviceRequestService->ktvRespondProposal($proposalId, $ktvId, $accept);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: $result->getData(),
            message: __('admin.service_request.messages.respond_success')
        );
    }

    /**
     * Tự động giải quyết ID của Dịch vụ hoặc Danh mục (xử lý sai số làm tròn số float 64-bit từ JavaScript)
     */
    protected function resolveServiceOrCategoryId(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $strVal = (string) $value;

        // 1. Kiểm tra khớp chính xác ID
        if (Category::where('id', $strVal)->exists() || Service::where('id', $strVal)->exists()) {
            return $strVal;
        }

        // 2. Nếu client JavaScript (Number) bị làm tròn số dấu phẩy động 64-bit BigInt (Number.MAX_SAFE_INTEGER = 9e15)
        if (is_numeric($strVal) && (float) $strVal > 1e12) {
            $num = (float) $strVal;
            $min = sprintf('%.0f', $num - 256);
            $max = sprintf('%.0f', $num + 256);

            $matchedCat = Category::whereBetween('id', [$min, $max])->first();
            if ($matchedCat) {
                return (string) $matchedCat->id;
            }

            $matchedService = Service::whereBetween('id', [$min, $max])->first();
            if ($matchedService) {
                return (string) $matchedService->id;
            }
        }

        return null;
    }
}

