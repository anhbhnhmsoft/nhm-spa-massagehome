<?php

namespace App\Services;

use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Enums\InvitationStatus;
use App\Enums\NotificationType;
use App\Enums\ProposalStatus;
use App\Enums\ServiceRequestStatus;
use App\Events\ProposalRespondedEvent;
use App\Models\KtvProactiveInvite;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestProposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProactiveMatchingService extends BaseService
{
    public function __construct(
        protected ServiceRequestService $serviceRequestService,
        protected NotificationService $notificationService
    ) {
        parent::__construct();
    }

    /**
     * KTV Quét danh sách Khách hàng đang bật nhu cầu xung quanh (Có Privacy Filter & Haversine Distance)
     */
    public function getNearbyDemandsForKtv(string $ktvId, ?float $lat = null, ?float $lng = null, float $radiusKm = 15.0): ServiceReturn
    {
        try {
            $query = ServiceRequest::with(['service', 'customer'])
                ->whereIn('status', [ServiceRequestStatus::NEW->value, ServiceRequestStatus::SEARCHING_KTV->value])
                ->whereHas('customer', function ($q) {
                    $q->where('is_proactive_matching_enabled', true);
                });

            $requests = $query->get()->map(function ($req) use ($lat, $lng) {
                // Tính khoảng cách Haversine nếu có lat/lng
                $distanceKm = null;
                if ($lat && $lng && $req->latitude && $req->longitude) {
                    $distanceKm = $this->calculateHaversineDistance($lat, $lng, (float)$req->latitude, (float)$req->longitude);
                }

                // Privacy Guard: Masks PII (Tên hiển thị, Ẩn SĐT, Chỉ hiện Phường/Thành phố)
                $customerName = $req->customer?->name ?? ('Khách hàng #' . substr($req->customer_id, 0, 6));
                $maskedCustomer = [
                    'id' => $req->customer_id,
                    'display_name' => $customerName,
                    'avatar' => null, // Ẩn avatar trước khi ghép đôi thành công
                ];

                $areaText = implode(', ', array_filter([$req->ward, $req->province])) ?: 'Khu vực gần bạn';

                return [
                    'request_id' => $req->id,
                    'service_name' => $req->service?->name ?? 'Dịch vụ Massage',
                    'preferred_techniques' => $req->preferred_techniques,
                    'preferred_date' => $req->preferred_date,
                    'time_slot' => $req->time_slot,
                    'urgency_level' => $req->urgency_level,
                    'ward' => $req->ward,
                    'province' => $req->province,
                    'relative_address' => $areaText,
                    'distance_km' => $distanceKm ? round($distanceKm, 1) : null,
                    'customer' => $maskedCustomer,
                    'created_at' => $req->created_at,
                ];
            });

            // Sắp xếp theo khoảng cách nếu có
            if ($lat && $lng) {
                $requests = $requests->sortBy('distance_km')->values();
            }

            return ServiceReturn::success($requests);
        } catch (\Throwable $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * KTV Gửi lời mời trực tiếp cho Khách hàng (Kèm Anti-Spam Guard)
     */
    public function sendInviteFromKtv(string $ktvId, string $customerId, ?int $requestId = null, ?string $note = null): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($ktvId, $customerId, $requestId, $note) {
                // Anti-Spam Guard 1: Tối đa 3 lời mời pending cùng lúc cho mỗi KTV
                $pendingCount = KtvProactiveInvite::where('ktv_id', $ktvId)
                    ->where('status', InvitationStatus::PENDING)
                    ->count();

                if ($pendingCount >= 3) {
                    return ServiceReturn::error(__('admin.proactive_invite.messages.invite_limit_reached'));
                }

                // Anti-Spam Guard 2: Cooldown 60 phút nếu khách từ chối lời mời gần đây
                $recentDecline = KtvProactiveInvite::where('ktv_id', $ktvId)
                    ->where('customer_id', $customerId)
                    ->where('status', InvitationStatus::DECLINED)
                    ->where('updated_at', '>=', now()->subMinutes(60))
                    ->exists();

                if ($recentDecline) {
                    return ServiceReturn::error(__('admin.proactive_invite.messages.cooldown_active'));
                }

                // Tạo lời mời mới
                $invite = KtvProactiveInvite::create([
                    'ktv_id' => $ktvId,
                    'customer_id' => $customerId,
                    'request_id' => $requestId,
                    'status' => InvitationStatus::PENDING,
                    'note' => $note,
                    'expires_at' => now()->addMinutes(20),
                ]);

                // Bắn thông báo đẩy cho Khách hàng
                try {
                    $this->notificationService->sendMobileNotification(
                        userId: (int)$customerId,
                        type: NotificationType::NOTIFICATION_MARKETING,
                        data: [
                            'title' => 'Bạn nhận được Lời mời trực tiếp từ KTV!',
                            'body' => 'Một KTV phù hợp gần bạn vừa gửi lời mời phục vụ. Bấm để xem Profile KTV.',
                            'invite_id' => $invite->id,
                        ]
                    );
                } catch (\Throwable $e) {
                    // Log error but allow invite creation
                }

                return ServiceReturn::success($invite->load(['ktv', 'customer']));
            });
        } catch (\Throwable $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Khách hàng Phản hồi Lời mời từ KTV (Chấp nhận ➔ Auto-Booking 1-Click / Từ chối)
     */
    public function customerRespondInvite(int $inviteId, string $customerId, bool $accept): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($inviteId, $customerId, $accept) {
                $invite = KtvProactiveInvite::with(['ktv', 'serviceRequest'])
                    ->lockForUpdate()
                    ->find($inviteId);

                if (!$invite || $invite->customer_id !== $customerId || $invite->status !== InvitationStatus::PENDING) {
                    return ServiceReturn::error(__('admin.proactive_invite.messages.invite_not_found'));
                }

                if ($accept) {
                    $invite->status = InvitationStatus::ACCEPTED;
                    $invite->save();

                    // Tự động hết hạn các lời mời khác của Khách này
                    KtvProactiveInvite::where('customer_id', $customerId)
                        ->where('id', '!=', $invite->id)
                        ->where('status', InvitationStatus::PENDING)
                        ->update(['status' => InvitationStatus::EXPIRED->value]);

                    // Nếu có ServiceRequest -> Đổi trạng thái MATCHED & Tạo 1-Click Booking
                    $booking = null;
                    if ($invite->serviceRequest) {
                        $invite->serviceRequest->status = ServiceRequestStatus::MATCHED;
                        $invite->serviceRequest->save();

                        $bookingResult = $this->serviceRequestService->createBookingFromRequest($invite->serviceRequest, $invite->ktv_id);
                        $booking = $bookingResult->getData();
                    }

                    // Dispatch Event & Notification
                    ProposalRespondedEvent::dispatch(
                        new ServiceRequestProposal([
                            'request_id' => $invite->request_id ?? 0,
                            'ktv_id' => $invite->ktv_id,
                            'status' => ProposalStatus::CUSTOMER_ACCEPTED,
                        ]),
                        'customer',
                        true
                    );

                    return ServiceReturn::success([
                        'invite' => $invite,
                        'booking' => $booking,
                    ]);
                } else {
                    $invite->status = InvitationStatus::DECLINED;
                    $invite->save();

                    return ServiceReturn::success(['invite' => $invite]);
                }
            });
        } catch (\Throwable $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Khách hàng Bật/Tắt chế độ nhận đề xuất từ KTV gần đây
     */
    public function toggleCustomerMatchingStatus(string $customerId, bool $enabled): ServiceReturn
    {
        try {
            User::where('id', $customerId)->update(['is_proactive_matching_enabled' => $enabled]);
            return ServiceReturn::success(['is_proactive_matching_enabled' => $enabled]);
        } catch (\Throwable $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Tính khoảng cách Haversine theo km giữa 2 tọa độ GPS
     */
    private function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadiusKm * $c;
    }
}
