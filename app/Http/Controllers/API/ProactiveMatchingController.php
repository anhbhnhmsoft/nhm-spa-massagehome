<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Enums\InvitationStatus;
use App\Models\KtvProactiveInvite;
use App\Services\ProactiveMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProactiveMatchingController extends BaseController
{
    public function __construct(
        protected ProactiveMatchingService $proactiveMatchingService
    ) {}

    /**
     * KTV Quét danh sách Khách hàng đang bật nhu cầu gần khu vực
     */
    public function nearbyDemands(Request $request): JsonResponse
    {
        $ktvId = (string) Auth::id();
        $lat = $request->query('lat') ? (float) $request->query('lat') : null;
        $lng = $request->query('lng') ? (float) $request->query('lng') : null;
        $radiusKm = $request->query('radius') ? (float) $request->query('radius') : 15.0;

        $result = $this->proactiveMatchingService->getNearbyDemandsForKtv($ktvId, $lat, $lng, $radiusKm);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(data: $result->getData());
    }

    /**
     * KTV Gửi Lời mời trực tiếp cho Khách hàng
     */
    public function sendInvite(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|string|exists:users,id',
            'request_id' => 'nullable|integer|exists:service_requests,id',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(errors: $validator->errors()->toArray());
        }

        $ktvId = (string) Auth::id();
        $customerId = (string) $request->input('customer_id');
        $requestId = $request->input('request_id') ? (int) $request->input('request_id') : null;
        $note = $request->input('note');

        $result = $this->proactiveMatchingService->sendInviteFromKtv($ktvId, $customerId, $requestId, $note);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: $result->getData(),
            message: __('admin.proactive_invite.messages.send_success')
        );
    }

    /**
     * Khách hàng Lấy danh sách Lời mời trực tiếp từ KTV
     */
    public function getCustomerInvites(Request $request): JsonResponse
    {
        $customerId = (string) Auth::id();
        $invites = KtvProactiveInvite::with(['ktv'])
            ->where('customer_id', $customerId)
            ->where('status', InvitationStatus::PENDING)
            ->where('expires_at', '>', now())
            ->get();

        return $this->sendSuccess(data: $invites);
    }

    /**
     * Khách hàng Phản hồi Lời mời từ KTV (Đồng ý / Từ chối)
     */
    public function respondInvite(Request $request, int $inviteId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'accept' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(errors: $validator->errors()->toArray());
        }

        $customerId = (string) Auth::id();
        $accept = (bool) $request->input('accept');

        $result = $this->proactiveMatchingService->customerRespondInvite($inviteId, $customerId, $accept);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: $result->getData(),
            message: __('admin.service_request.messages.respond_success')
        );
    }

    /**
     * Khách hàng Bật/Tắt chế độ nhận lời mời trực tiếp từ KTV
     */
    public function toggleStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(errors: $validator->errors()->toArray());
        }

        $customerId = (string) Auth::id();
        $enabled = (bool) $request->input('enabled');

        $result = $this->proactiveMatchingService->toggleCustomerMatchingStatus($customerId, $enabled);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(data: $result->getData());
    }
}
