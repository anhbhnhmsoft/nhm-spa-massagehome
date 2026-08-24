<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Enums\KtvTechnique;
use App\Enums\UrgencyLevel;
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
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|integer|exists:services,id',
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
    public function respondProposalByCustomer(Request $request, int $proposalId): JsonResponse
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
    public function respondProposalByKtv(Request $request, int $proposalId): JsonResponse
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
}
